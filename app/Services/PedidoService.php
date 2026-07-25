<?php

namespace App\Services;

use App\Models\TOrdene;
use App\Models\TEstatusOrdene;
use App\Models\Scopes\OperadorFabricante;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PedidoService
{

    public function __construct(
        protected CompanyContextService $contextService)
    {}

    /**
     * Obtiene órdenes filtradas con permisos aplicados mediante contexto.
     */
    public function getOrdenesFiltradas(
        ?int $estatusId = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        ?string $rfvId = null,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {

        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        // Iniciamos quitando el scope global que suele filtrar por el idFabricante del usuario logueado
        $query = TOrdene::withoutGlobalScope(OperadorFabricante::class);

        // 1. Aplicar Filtro de Seguridad por Contexto
        if ($user->idgrupo_persona === 'RFV') {
            $query->where('idpasadopor', $user->idPersona);
        } elseif ($activeFabricante) {
            // SIIF, GRT o SUP con empresa seleccionada
            $query->whereHas('rfv', function ($q) use ($activeFabricante, $rfvId) {
                $q->withoutGlobalScope(OperadorFabricante::class)
                ->where('idFabricante', $activeFabricante);

                if ($rfvId) {
                    $q->where('idPersona', $rfvId);
                }
            });
        } else {
            // SIIF sin empresa seleccionada: no devolvemos nada por seguridad
            return new LengthAwarePaginator([], 0, $perPage);
        }

        // 2. Filtros de Negocio
        if ($estatusId) $query->where('idestatus', $estatusId);
        if ($fechaInicio && $fechaFin) {
            $inicio = min($fechaInicio, $fechaFin);
            $fin    = max($fechaInicio, $fechaFin);
            $query->whereBetween('fechaOrden', [$inicio, $fin]);
        }

        // 3. Carga de relaciones (Estandarizado)
        // estatus/factura tienen su propio OperadorFabricante scope (filtra
        // por el idOperador/idFabricante del usuario AUTENTICADO), que no
        // tiene nada que ver con el idFabricante de la orden ni con el
        // contexto activo (SIIF/SUP/GRT viendo otra empresa) - sin quitarlo,
        // el eager load casi nunca encuentra coincidencia.
        $query->with([
            'cliente', 'rfv', 'mayorista', 'mayoristas',
            'estatus' => fn($q) => $q->withoutGlobalScope(OperadorFabricante::class),
            'productos',
            'factura' => fn($q) => $q->withoutGlobalScope(OperadorFabricante::class),
        ]);

        $results = $query->orderBy('fechaOrden', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $results->getCollection()->transform(fn($orden) => $this->formatearOrdenLista($orden));

        return $results;
    }

    /**
     * Obtiene el detalle de una orden validando pertenencia.
     */
    public function getOrdenDetalle(int $ordenId): ?array
    {
        $activeFabricante = $this->contextService->getActiveId();
        $user = Auth::user();

        $query = TOrdene::withoutGlobalScope(OperadorFabricante::class)
        ->with([
            'cliente', 'rfv', 'mayorista', 'mayoristas',
            'estatus' => fn($q) => $q->withoutGlobalScope(OperadorFabricante::class),
            'productos',
            'factura' => fn($q) => $q->withoutGlobalScope(OperadorFabricante::class),
        ]);

        $orden = $query->find($ordenId);

        if (!$orden) return null;

        // Validación de seguridad rápida:
        // ¿La orden pertenece al RFV logueado O al fabricante activo en el contexto?
        $rfv = $orden->rfv()->withoutGlobalScope(OperadorFabricante::class)->first();

        if ($user->idgrupo_persona === 'RFV' && $orden->idpasadopor !== $user->idPersona) return null;
        if ($user->idgrupo_persona !== 'RFV' && $rfv?->idFabricante !== $activeFabricante) return null;

        return $this->formatearOrdenDetalle($orden);
    }

    /**
     * Obtiene estatus según el fabricante activo.
     */
    public function getEstatusDisponibles(): array
    {
        $activeFabricante = $this->contextService->getActiveId();

        $query = TEstatusOrdene::withoutGlobalScope(OperadorFabricante::class);

        if ($activeFabricante) {
            $query->where('idFabricante', $activeFabricante);
        }

        return $query->get(['idestatus', 'descripcion'])->toArray();
    }

    public function actualizarEstatusOrden(int $ordenId, int $nuevoEstatus): ?TOrdene
    {
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        $orden = TOrdene::withoutGlobalScope(OperadorFabricante::class)->find($ordenId);
        if (!$orden) return null;

        $rfv = $orden->rfv()->withoutGlobalScope(OperadorFabricante::class)->first();
        if ($user->idgrupo_persona !== 'SIIF' && $rfv?->idFabricante !== $activeFabricante) {
            return null;
        }

        $orden->idestatus = $nuevoEstatus;
        $orden->save();

        return $orden->fresh([
            'estatus' => fn($q) => $q->withoutGlobalScope(OperadorFabricante::class),
        ]);
    }

    /**
     * Formatea orden para listado (panel izquierdo)
     */
    private function formatearOrdenLista(TOrdene $orden): array
    {
        return [
            'nOrden' => $orden->idorden,
            'estatus' => $orden->estatus->descripcion ?? 'Sin estatus',
            'cliente' => $orden->cliente->nombre_completo_razon_social ?? 'Sin cliente',
            'persona' => $orden->rfv->nombre_completo_razon_social ?? 'Sin RFV',
            'fecha' => $orden->fechaOrden ? $orden->fechaOrden->format('d/m/Y') : 'Sin fecha',
            'total' => (float) ($orden->costoTotal ?? 0),
            'cantidad_unidades' => (int) ($orden->TotalUnidades ?? 0),
        ];
    }

    /**
     * Formatea orden completa para detalle (panel derecho)
     */
    private function formatearOrdenDetalle(TOrdene $orden): array
    {
        // Combinar mayorista principal con secundarios
        $mayoristas = collect();

        if ($orden->mayorista) {
            $mayoristas->push([
                'id' => $orden->mayorista->idPersona,
                'nombre' => $orden->mayorista->nombre_completo_razon_social,
                'descuento' => 0
            ]);
        }

        if ($orden->mayoristas && $orden->mayoristas->isNotEmpty()) {
            foreach ($orden->mayoristas as $mayorista) {
                $mayoristas->push([
                    'id' => $mayorista->idPersona,
                    'nombre' => $mayorista->nombre_completo_razon_social,
                    'descuento' => $mayorista->pivot->item_descuento ?? 0
                ]);
            }
        }

        // Determinar si está facturada
        $estaFacturada = $orden->factura !== null;

        // Formatear productos
        $productos = [];
        if ($orden->productos && $orden->productos->isNotEmpty()) {
            $productos = $orden->productos->map(function($producto) use ($estaFacturada) {
                $cantidadMostrar = $estaFacturada
                ? ($producto->pivot->cantidad_conciliada ?? 0)
                : ($producto->pivot->cantidad_solicitada ?? 0);

                return [
                    'id' => $producto->idproducto,
                    'nombre' => $producto->nombre_producto ?? 'Sin nombre',
                    'cantidad' => $producto->pivot->cantidad_solicitada ?? 0,
                    'precio' => $producto->pivot->item_price ?? 0,
                    'unidades' => $producto->pivot->idunidades ?? 0,
                    'descuento' => $producto->pivot->item_descuento ?? 0,
                    'conciliada' => $producto->pivot->cantidad_conciliada ?? 0,
                    'cantidad_mostrar' => $cantidadMostrar,
                ];
            })->toArray();
        } else {
            Log::warning('No hay productos para formatear');
        }

        // Coordenadas
        $coordenadas = [
            'latitud' => $orden->coordenadas_l ?? '',
            'longitud' => $orden->coordenadas_a ?? '',
        ];

        // Ubicación del cliente
        $ubicacionCliente = [
            'estado' => $orden->cliente->idestado ?? 'Sin estado',
            'ciudad' => $orden->cliente->idciudad ?? 'Sin ciudad',
        ];

        return [
            'nOrden' => $orden->idorden,
            'estatus' => $orden->estatus->descripcion ?? 'Sin estatus',
            'cliente' => $orden->cliente->nombre_completo_razon_social ?? 'Sin cliente',
            'persona' => $orden->rfv->nombre_completo_razon_social ?? 'Sin RFV',
            'mayoristas' => $mayoristas->toArray(),
            'productos' => $productos,
            'fecha' => $orden->fechaOrden ? $orden->fechaOrden->format('d/m/Y') : 'Sin fecha',
            'total' => (float) ($orden->costoTotal ?? 0),
            'impuesto' => (float) ($orden->impuesto ?? 0),
            'cantidad_unidades' => (int) ($orden->TotalUnidades ?? 0),
            'comentario' => $orden->comentario_entrega ?? 'Sin comentarios',
            'factura' => $estaFacturada ? [
                'idfactura' => $orden->factura->idfactura,
                'fechaFactura' => $orden->factura->fechaFactura
                ? $orden->factura->fechaFactura->format('d/m/Y')
                : 'Sin fecha',
            ] : null,
            'estaFacturada' => $estaFacturada,
            'coordenadas' => $coordenadas,
            'ubicacion_cliente' => $ubicacionCliente,
        ];
    }
}
