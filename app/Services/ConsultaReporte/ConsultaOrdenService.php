<?php

namespace App\Services\ConsultaReporte;

use App\Models\{TEstatusOrdene, TFacturas, TEstado, TOrdene, TPersona};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use App\Services\CompanyContextService;
use Carbon\Carbon;

class ConsultaOrdenService
{
    public function __construct(protected CompanyContextService $contextService) {}

    
    public function consultar(array $filters, Request $request): array
    {
        $user = Auth::user();
        $fabricante = $this->contextService->getActiveId();

        $query = $this->buildBaseQuery($filters, $user, $fabricante);

        // Filtros
        $inicio = min($filters['fechaInicio'], $filters['fechaFin']) . ' 00:00:00';
        $fin    = max($filters['fechaInicio'], $filters['fechaFin']) . ' 23:59:59';
        $query->whereBetween('ord.fechaOrden', [$inicio, $fin]);


        if (!empty($filters['estatus'])) {
            $ids = collect($filters['estatus'])->pluck('idestatus')->filter()->map(fn($v) => (int) $v)->values()->toArray();
            if (!empty($ids)) $query->whereIn('ord.idestatus', $ids);
        }

        if (!empty($filters['mayorista'])) {
            $ids = collect($filters['mayorista'])->pluck('idPersona')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('ord.idMayorista', $ids);
        }

        $query->orderBy('ord.fechaOrden', 'desc');

        // Calcular totales de toda la consulta (sin paginación)
        $totalesQuery = clone $query;
        $totalesQuery->getQuery()->columns = [];
        $totalesQuery->getQuery()->orders = null; 

        $totales = $totalesQuery->selectRaw('SUM(ord.TotalUnidades) as total_unidades, SUM(ord.costoTotal) as total_monto')->first();

        // Paginación
        $paginado = $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1)
        );


        if ($paginado->isEmpty()) {
            return ['error' => 'No hay actividades que cumplan con estos parámetros'];
        }

        $ordenesData = $paginado->getCollection();
        $nordenIds = $ordenesData->pluck('idorden')->toArray();

        return [
            'ordenes'   => $this->formatOrdenes($ordenesData),
            'productos' => $this->getProductosPorOrdenes($nordenIds),
            'totalUnidades' => (int) $totales->total_unidades,
            'montoTotal'    => (float) $totales->total_monto,
            'pagination' => [
                'total'        => $paginado->total(),
                'current_page' => $paginado->currentPage(),
                'per_page'     => $paginado->perPage(),
                'last_page'    => $paginado->lastPage(),
            ]
        ];
    }

        public function exportarTodo(array $filters): array
    {
        $user       = Auth::user();
        $fabricante = $this->contextService->getActiveId();

        $query = $this->buildBaseQuery($filters, $user, $fabricante);
        $inicio = min($filters['fechaInicio'], $filters['fechaFin']) . ' 00:00:00';
        $fin    = max($filters['fechaInicio'], $filters['fechaFin']) . ' 23:59:59';
        $query->whereBetween('ord.fechaOrden', [$inicio, $fin]);

        if (!empty($filters['estatus'])) {
            $ids = collect($filters['estatus'])->pluck('idestatus')->filter()->map(fn($v) => (int)$v)->values()->toArray();
            if (!empty($ids)) $query->whereIn('ord.idestatus', $ids);
        }
        if (!empty($filters['mayorista'])) {
            $ids = collect($filters['mayorista'])->pluck('idPersona')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('ord.idMayorista', $ids);
        }

        $query->orderBy('ord.fechaOrden', 'desc');

        $ordenes   = $query->get();
        $ordenIds  = $ordenes->pluck('idorden')->toArray();

        return [
            'ordenes'   => collect($this->formatOrdenes($ordenes)),
            'productos' => collect($this->getProductosPorOrdenes($ordenIds)),
        ];
    }
    // Logica de consulta y formateo
    private function buildBaseQuery(array $filters, $user, ?string $fabricante)
    {
        $userGroup = $user->idgrupo_persona;

        $query = TOrdene::withoutGlobalScopes()
            ->from('t_ordenes as ord')
            ->leftJoin('t_personas as cli', 'ord.idPersona_solicitante', '=', 'cli.idPersona')
            ->leftJoin('t_personas as rfv', 'ord.idPersona', '=', 'rfv.idPersona')
            ->leftJoin('t_estatus_ordenes as est', 'ord.idestatus', '=', 'est.idestatus')
            ->leftJoin('t_facturas as fac', 'ord.idorden', '=', 'fac.idordenes')
            ->leftJoin('t_ciudades as ciu', 'cli.idciudad', '=', 'ciu.idCiudad')
            ->leftJoin('t_estados as esta', 'cli.idestado', '=', 'esta.idestado')
            ->leftJoin('t_personas as mayo', 'ord.idMayorista', '=', 'mayo.idPersona')
            ->select([
                'ord.*',
                'esta.nombreCiudad as estado_nombre',
                'ciu.nombreCiudad as ciudad_nombre',
                'cli.nombre_completo_razon_social as cliente_nombre',
                'rfv.nombre_completo_razon_social as rfv_nombre',
                'est.descripcion as estatus_descripcion',
                'fac.idfactura as factura_id',
                'mayo.nombre_completo_razon_social as mayorista_nombre',
                'ord.TotalUnidades as unidades_total',
                'ord.comentario_entrega as comentarios',
                'ord.coordenadas_l',
                'ord.coordenadas_a',
                
            ]);

        if (!empty($filters['rfv'])) {
            $ids = ($userGroup === 'RFV')
                ? [$user->idPersona]
                : collect($filters['rfv'])->pluck('idPersona')->filter()->values()->toArray();

            $query->whereIn('ord.idPersona', $ids);
            return $query;
        }

        if ($userGroup === 'SIIF') {
            if (!empty($fabricante)) {
                $query->where('ord.idFabricante', $fabricante);
            }
        } elseif (in_array($userGroup, ['GRT', 'SUP'])) {
            $query->where('ord.idFabricante', $fabricante);
        } else {
            $query->where('ord.idPersona', $user->idPersona);
        }

        return $query;
    }

    private function getProductosPorOrdenes(array $ordenIds): array
    {
        return DB::table('t_item_ordenes')
            ->join('t_ordenes', 't_item_ordenes.idorden', '=', 't_ordenes.idorden')
            ->join('t_personas as cliente', 'cliente.idPersona', '=', 't_ordenes.idPersona_solicitante')
            ->join('t_personas as rfv', 'rfv.idPersona', '=', 't_ordenes.idPersona')
            ->join('t_personas as mayo', 'mayo.idPersona', '=', 't_ordenes.idMayorista')
            ->whereIn('t_item_ordenes.idorden', $ordenIds)
            ->select([
                DB::raw('COALESCE(cliente.nombre_completo_razon_social, "N/A") as Cliente'),
                DB::raw('COALESCE(rfv.nombre_completo_razon_social, "N/A") as RFV'),
                't_item_ordenes.idorden as Orden',
                DB::raw('COALESCE(t_item_ordenes.nombreproducto, "N/A") as Nombre'),
                DB::raw('COALESCE(mayo.nombre_completo_razon_social, "N/A") as Mayorista'),
                DB::raw('COALESCE(SUM(cantidad_solicitada), 0) as Solicitado'),
                DB::raw('COALESCE(SUM(item_price * cantidad_solicitada), 0) as Monto_Solicitado'),
                DB::raw('COALESCE(SUM(cantidad_Faltante), 0) as Faltante'),
                DB::raw('COALESCE(SUM(cantidad_conciliada), 0) as Conciliado'),
            ])
            ->groupBy(
                't_item_ordenes.idorden',
                't_item_ordenes.nombreproducto',
                't_item_ordenes.idMayorista',
                'cliente.nombre_completo_razon_social',
                'rfv.nombre_completo_razon_social',
                'mayo.nombre_completo_razon_social',
            )
            ->get()
            ->toArray();
    }

    private function formatOrdenes($ordenes): array
    {
        return $ordenes->map(fn ($item) => [
            'nOrden'     => $item->idorden,
            'estatus'    => $item->estatus_descripcion ?? 'N/A',
            'cliente'    => $item->cliente_nombre ?? 'N/A',
            'rfv'    => $item->rfv_nombre ?? 'N/A',
            'fecha'      => Carbon::parse($item->fechaOrden)->toDateString(),
            'factura'    => $item->factura_id ?? '',
            'totalOrden' => (float) $item->costoTotal,
            'ciudad' => $item->ciudad_nombre ?? 'N/A',
            'estado' => $item->estado_nombre ?? 'N/A',
            'mayoristas' => $item->mayorista_nombre ?? 'N/A',
            'unidades' => $item->unidades_total ?? 0,
            'comentario' => $item->comentarios ?? 'N/A',
            'coordenadas_l' => $item->coordenadas_l ?? 'N/A',
            'coordenadas_a' => $item->coordenadas_a ?? 'N/A',
        ])->toArray();
    }
}