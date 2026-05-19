<?php

//app/Services/ConciliarFactService.php
namespace App\Services;

use App\Models\TPersona;
use App\Models\TOrdene;
use App\Models\TFormaPago;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Traits\FabricanteTrait;

class ConciliarFactService
{
    use FabricanteTrait;

    public function __construct(
        protected CompanyContextService $contextService)
    {}

    /**
     * Obtiene las empresas disponibles según el contexto.
     */
    public function getEmpresasMonitor(): Collection
    {
        $user = Auth::user();
        
        // Si es SIIF, trae todas las FABR. Si es GRT, solo la suya.
        return TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'FABR')
            ->when($user->idgrupo_persona === 'GRT', function($q) use ($user) {
                $q->where('idFabricante', $user->idFabricante);
            })
            ->orderBy('nombre_completo_razon_social')
            ->get(['idPersona as id', 'nombre_completo_razon_social as nombre'])
            ->map(fn($emp) => [
                'id' => (string) $emp->id,
                'nombre' => $emp->nombre
            ]);
    }

    /**
     * Obtiene solo la información básica de las órdenes (sin productos) para el select
     */
    public function getOrdenesParaSelect(?string $idFabricante, Request $request): LengthAwarePaginator
    {
        // Si no hay fabricante (caso SIIF sin selección), devolvemos paginador vacío
        if (!$idFabricante) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $search = $request->input('search') ?? $request->input('orden');
        $size = $request->input('size', 25);

        return TOrdene::withoutGlobalScopes()
            ->with([
                'cliente:idPersona,nombre_completo_razon_social',
                'rfv:idPersona,nombre_completo_razon_social'
            ])
            ->where('idFabricante', $idFabricante)
            ->whereNull('idfactura')
            ->when($search, fn($q) => $q->where('idorden', 'LIKE', "%{$search}%"))
            ->orderBy('fechaOrden', 'desc')
            ->paginate($size)
            ->through(fn($orden) => [
                'id' => (string) $orden->idorden, // Siempre string para Vue
                'nombre' => "Orden #{$orden->idorden}",
                'cliente' => $orden->cliente?->nombre_completo_razon_social ?? 'N/A',
                'fecha' => $orden->fechaOrden->format('d/m/Y'),
                'total' => (float) ($orden->costoTotal ?? 0)
            ]);
    }

        /**
     * Búsqueda dinámica de órdenes
     */
    public function buscarOrdenes(?string $idFabricante, string $term): array
    {
        if (!$idFabricante) return [];

        return TOrdene::withoutGlobalScopes()
            ->with(['cliente:idPersona,nombre_completo_razon_social'])
            ->where('idFabricante', $idFabricante)
            ->whereNull('idfactura')
            ->where(function($q) use ($term) {
                $q->where('idorden', 'LIKE', "%{$term}%")
                ->orWhereHas('cliente', fn($q2) =>
                    $q2->where('nombre_completo_razon_social', 'LIKE', "%{$term}%")
                );
            })
            ->orderBy('fechaOrden', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($orden) => [
                'value' => (string) $orden->idorden,
                'label' => "{$orden->idorden} - " . ($orden->cliente?->nombre_completo_razon_social ?? 'N/A') . " ({$orden->fechaOrden->format('d/m/Y')})",
            ])
            ->toArray();
    }
    /**
     * Obtiene la información completa de una orden específica con paginación de productos
     */
    public function getOrdenConProductos(string $idOrden, Request $request): ?array
    {
        try {
            // 1. Obtener información básica de la orden
            $orden = TOrdene::withoutGlobalScopes()
                ->with([
                    'cliente:idPersona,nombre_completo_razon_social',
                    'rfv:idPersona,nombre_completo_razon_social',
                    'mayorista:idPersona,nombre_completo_razon_social',
                ])
                ->find($idOrden);

            if (!$orden) {
                return null;
            }

            // 2. Obtener productos paginados
            $productSize = $request->input('product_size', 15);
            $productPage = $request->input('product_page', 1);

            $productos = $orden->productos()
                ->withPivot(['cantidad_solicitada', 'item_price', 'item_descuento', 'item_total', 'cantidad_faltante', 'cantidad_conciliada'])
                ->paginate($productSize, ['*'], 'product_page', $productPage);
            
            $productos->appends($request->query());

            // 3. Formatear la respuesta
            $productos = $productos->through(function($producto) {
                return [
                    'id' => $producto->idproducto,
                    'codigo' => $producto->idproducto,
                    'producto' => $producto->nombre_producto,
                    'cantidad' => $producto->pivot->cantidad_solicitada ?? 0,
                    'precio' => $producto->pivot->item_price ?? 0,
                    'precio_total' => $producto->pivot->item_total ?? 0,
                    'descuento' => $producto->pivot->item_descuento ?? 0,
                    'conciliada' => $producto->pivot->cantidad_conciliada ?? 0,
                    'faltante' => $producto->pivot->cantidad_faltante ?? 0,
                ];
            });

            // 4. Formatear la respuesta final
            return [
                'orden' => [
                    'id' => $orden->idorden,
                    'cliente' => [
                        'id' => $orden->cliente?->idPersona,
                        'nombre' => $orden->cliente?->nombre_completo_razon_social ?? 'Cliente no disponible'
                    ],
                    'rfv' => [
                        'id' => $orden->rfv?->idPersona,
                        'nombre' => $orden->rfv?->nombre_completo_razon_social ?? 'RFV no disponible'
                    ],
                    'mayorista' => [
                        'id' => $orden->mayorista?->idPersona,
                        'nombre' => $orden->mayorista?->nombre_completo_razon_social ?? 'Mayorista no disponible'
                    ],
                    'total' => $orden->costoTotal ?? 0.0,
                    'fecha' => $orden->fechaOrden->format('d/m/Y'),
                    'registradoPor' => $orden->idpasadopor ?? 'N/A',
                    'impuesto' => $orden->impuesto ?? 0.0,
                    'costoTotal' => $orden->costoTotal ?? 0.0
                ],
                'productos' => $productos
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener orden con productos', [
                'error' => $e->getMessage(),
                'idOrden' => $idOrden,
            ]);
            return null;
        }
    }

    public function getFormasDePago(): Collection
        {
            return TFormaPago::all()->map(fn($f) => [
                'id' => (string) $f->idformaPago,
                'nombre' => $f->descripcion
            ]);
        }

}