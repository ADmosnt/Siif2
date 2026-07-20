<?php

namespace App\Services\ConsultaReporte;

use App\Services\CompanyContextService;
use App\Models\TActividadesRepresentante;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultaVisitaService
{
    public function __construct(protected CompanyContextService $contextService) {}

    
    public function consultar(array $filters, Request $request): array
    {
        $user       = Auth::user();
        $fabricante = $this->contextService->getActiveId();
        $operador   = $this->contextService->getActiveOperador();

        $query = $this->buildBaseQuery($filters, $user, $fabricante, $operador);
        $inicio = min($filters['fechaInicio'], $filters['fechaFin']) . ' 00:00:00';
        $fin    = max($filters['fechaInicio'], $filters['fechaFin']) . ' 23:59:59';
        $query->whereBetween('act.fecha_actividad', [$inicio, $fin]);

        if (!empty($filters['actividad'])) {
            $ids = collect($filters['actividad'])->pluck('value')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('act.idtipo_actividades', $ids);
        }

        if (!empty($filters['zona'])) {
            $ids = collect($filters['zona'])->pluck('idestado')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idestado', $ids);
        }

        if (!empty($filters['brick'])) {
            $ids = collect($filters['brick'])->pluck('idciudad')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idciudad', $ids);
        }

        if (!empty($filters['especialidad'])) {
            $ids = collect($filters['especialidad'])->pluck('id')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idactividad_negocio', $ids);
        }

        if (!empty($filters['ranking'])) {
            $ids = collect($filters['ranking'])->pluck('id')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idranking', $ids);
        }

        if (!empty($filters['incidente'])) {
            $ids = collect($filters['incidente'])->pluck('idtipo_incidentes')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('act.idtipo_incidentes', $ids);
        }

        $query->orderBy('act.fecha_actividad', 'desc');

        $paginado = $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1)
        );

        if ($paginado->isEmpty()) {
            return ['error' => 'No hay actividades que cumplan con estos parámetros'];
        }

        $visitas    = $paginado->getCollection();
        $reporteIds = $visitas->pluck('idreporte')->toArray();

        return [
            'visitas'    => $this->formatVisitas($visitas),
            'productos'  => $this->getProductosPorVisitas($reporteIds),
            'pagination' => [
                'total'        => $paginado->total(),
                'current_page' => $paginado->currentPage(),
                'per_page'     => $paginado->perPage(),
                'last_page'    => $paginado->lastPage(),
            ],
        ];
    }
    public function exportarTodo(array $filters): array
    {
        $user       = Auth::user();
        $fabricante = $this->contextService->getActiveId();
        $operador   = $this->contextService->getActiveOperador();

        $query = $this->buildBaseQuery($filters, $user, $fabricante, $operador);
        $inicio = min($filters['fechaInicio'], $filters['fechaFin']) . ' 00:00:00';
        $fin    = max($filters['fechaInicio'], $filters['fechaFin']) . ' 23:59:59';
        $query->whereBetween('act.fecha_actividad', [$inicio, $fin]);

        if (!empty($filters['actividad'])) {
            $ids = collect($filters['actividad'])->pluck('value')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('act.idtipo_actividades', $ids);
        }

        if (!empty($filters['zona'])) {
            $ids = collect($filters['zona'])->pluck('idestado')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idestado', $ids);
        }

        if (!empty($filters['brick'])) {
            $ids = collect($filters['brick'])->pluck('idciudad')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idciudad', $ids);
        }

        if (!empty($filters['especialidad'])) {
            $ids = collect($filters['especialidad'])->pluck('id')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idactividad_negocio', $ids);
        }

        if (!empty($filters['ranking'])) {
            $ids = collect($filters['ranking'])->pluck('id')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('cli.idranking', $ids);
        }

        if (!empty($filters['incidente'])) {
            $ids = collect($filters['incidente'])->pluck('idtipo_incidentes')->filter()->values()->toArray();
            if (!empty($ids)) $query->whereIn('act.idtipo_incidentes', $ids);
        }

        $query->orderBy('act.fecha_actividad', 'desc');

        $visitas    = $query->get();
        $reporteIds = $visitas->pluck('idreporte')->toArray();

        return [
            'visitas'  => collect($this->formatVisitas($visitas)),
            'muestras' => collect($this->getProductosPorVisitas($reporteIds)),
        ];
    }

    private function buildBaseQuery(array $filters, $user, ?string $fabricante, $operador)
    {
        $userGroup = $user->idgrupo_persona;

        $query = TActividadesRepresentante::withoutGlobalScopes()
            ->from('t_actividades_representante as act')
            ->join('t_personas as cli', 'act.idCliente', '=', 'cli.idPersona')
            ->leftJoin('t_ciudades as ciu', 'cli.idciudad', '=', 'ciu.idCiudad')
            ->leftJoin('t_estados as est', 'cli.idestado', '=', 'est.idestado')
            ->leftJoin('t_personas as rfv', 'act.idRFV', '=', 'rfv.idPersona')
            ->leftJoin('t_tipo_actividades as tact','act.idtipo_actividades','=', 'tact.idtipo_actividades')
            ->leftJoin('t_especialidades as esp', 'cli.idactividad_negocio', '=', 'esp.id')
            ->leftJoin('t_ranking_clientes as rank', 'cli.idranking', '=', 'rank.id')
            ->select([
                'act.*',
                'cli.nombre_completo_razon_social as cliente_nombre',
                'cli.idactividad_negocio',
                'cli.idranking',
                'rfv.nombre_completo_razon_social as rfv_nombre',
                'ciu.nombreCiudad as ciudad_nombre',
                'est.nombreCiudad as estado_nombre',
                'tact.descripcion_tipo_actividades as actividad_desc',
                'esp.descripcion_especialidad as especialidad_desc',
                'rank.descripcion_ranking_cliente as ranking_desc',
            ]);

        $query->where('cli.idOperador', $operador);

        if ($fabricante) {
            $query->where('cli.idFabricante', $fabricante);
        }

        if (!empty($filters['rfv'])) {
            $ids = ($userGroup === 'RFV')
                ? [$user->idPersona]
                : collect($filters['rfv'])->pluck('idPersona')->filter()->values()->toArray();

            return $query->whereIn('act.idRFV', $ids);
        }

        match ($userGroup) {
            'SIIF' => null, // fabricante ya filtrado arriba
            'GRT', 'SUP' => null, // ídem
            default => $query->where('act.idRFV', $user->idPersona),
        };

        return $query;
    }

    private function getProductosPorVisitas(array $reporteIds): array
    {
        if (empty($reporteIds)) {
            return [];
        }

        return DB::table('r_actividades_productos as rap')
            ->join('t_actividades_representante as act', 'act.idreporte', '=', 'rap.idreporte')
            ->join('t_productos as p', 'p.idproducto', '=', 'rap.idproducto')
            ->join('t_personas as cli', 'act.idCliente', '=', 'cli.idPersona')
            ->join('t_personas as rfv', 'act.idRfv', '=', 'rfv.idPersona')
            ->whereIn('rap.idreporte', $reporteIds)
            ->select([
                'rap.idreporte as Reporte',
                DB::RAW ('COALESCE(rfv.nombre_completo_razon_social, "N/A") as RFV'),
                DB::RAW ('COALESCE(cli.nombre_completo_razon_social, "N/A") as Cliente'),
                DB::RAW ('COALESCE(p.nombre_producto, "N/A") as Material'),
                DB::RAW ('COALESCE(rap.cantidad, 0 ) as Cantidad'),
            ])
            ->get()
            ->toArray();
    }

    private function formatVisitas($visitas): array
    {
        return $visitas->map(fn ($item) => [
            'reporte' => $item->idreporte ?? 'N/A',
            'cliente' => $item->cliente_nombre ?? 'N/A',
            'rfv' => $item->rfv_nombre ?? 'N/A',
            'actividad' => $item->actividad_desc ?? 'N/A',
            'ciudad' => $item->ciudad_nombre ?? 'N/A',
            'estado' => $item->estado_nombre ?? 'N/A',
            'fecha' => Carbon::parse($item->fecha_actividad)->toDateString(),
            'observaciones' => $item->observaciones_cliente ?? 'N/A',
            'coordenadas_l' => $item->coordenadas_l ?? 'N/A',
            'coordenadas_a' => $item->coordenadas_a ?? 'N/A',
            'especialidad' => $item->especialidad_desc ?? 'N/A',
            'ranking' => $item->ranking_desc ?? 'N/A',
        ])->toArray();
    }
}
