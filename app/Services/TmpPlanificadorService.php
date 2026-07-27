<?php

//app/Services/TmpPlanificadorService.php
namespace App\Services;

use App\Models\TTmpPlanificadore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\TmpPlanificador\ValidationService;
use App\Services\TmpPlanificador\PreparationService;
use App\Services\TmpPlanificador\FilterService;
use App\Services\TmpPlanificador\QueryService;

class TmpPlanificadorService
{
    const COLOR_PENDIENTE = 'yellow';
    const COLOR_PERDIDA = 'red';
    const COLOR_PROCESADA = 'green';

    public function __construct(
        protected RepresentanteClienteService $representanteClienteService,
        protected CompanyContextService $contextService,
        protected ValidationService $validationService,
        protected PreparationService $preparationService,
        protected FilterService $filterService,
        protected QueryService $queryService,
    ) {}

    // =============================================================================
    // MÉTODOS PÚBLICOS PRINCIPALES
    // =============================================================================

    public function getEventosParaCalendario(Request $request): array
        {
            $validated = $this->validationService->validarParametrosCalendario($request);
            $year = (int) $validated['year'];
            $month = (int) $validated['month'];
            $idRfvFiltro = $validated['idRfv'] ?? null;

            $activeId = $this->contextService->getActiveId();
            if (!$activeId && Auth::user()->idgrupo_persona !== 'RFV') return [];

            $visitasTemporales = $this->queryService->obtenerVisitasTemporales($year, $month, $idRfvFiltro);
            $visitasPerdidas = $this->queryService->obtenerVisitasPerdidas($year, $month, $idRfvFiltro);
            $visitasProcesadas = $this->queryService->obtenerVisitasProcesadas($year, $month, $idRfvFiltro);

            return array_merge(
                $this->mapearVisitas($visitasTemporales, 'tmp_temporal', self::COLOR_PENDIENTE, true, true, true),
                $this->mapearVisitas($visitasPerdidas, 'tmp_perdida', self::COLOR_PERDIDA, false, false, false),
                $this->mapearVisitasProcesadas($visitasProcesadas)
            );
        }

    public function getDataParaProcesarVisita(int $idVisitaTemporal, Request $request): array
    {
        $user = Auth::user();
        
        if (in_array($user->idgrupo_persona, ['GRT', 'SUP'])) {
            throw new \Exception('No autorizado para procesar esta visita temporal.', 403);
        }
        
        $visitaTemporal = TTmpPlanificadore::with(['rfv', 'cliente'])->find($idVisitaTemporal);

        if (!$visitaTemporal) {
            throw new \Exception('Visita temporal no encontrada.', 404);
        }

        if ($visitaTemporal->idRFV != $user->idPersona && !in_array($user->idgrupo_persona, ['GRT', 'SUP'])) {
            throw new \Exception('No autorizado para procesar esta visita temporal.', 403);
        }

        return [
            'representantes' => $this->representanteClienteService->getRepresentantesData(
                $request->merge(['idRfv' => $visitaTemporal->idRFV])
            ),
            'visitaTemporal' => [
                'id' => $visitaTemporal->Id,
                'idRFV' => $visitaTemporal->idRFV,
                'nombre_rfv' => $visitaTemporal->rfv->nombre_completo_razon_social ?? 'No encontrado',
                'idCliente' => $visitaTemporal->idCliente,
                'nombre_cliente' => $visitaTemporal->cliente->nombre_completo_razon_social ?? 'No encontrado',
                'Fecha' => $visitaTemporal->Fecha,
                'Hora' => $visitaTemporal->Hora,
            ],
            'filtros' => $request->only(['search', 'size', 'page'])
        ];
    }

    public function listarVisitasTemporalesFiltradas(Request $request, ?string $year = null, ?string $month = null)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $size = $request->input('size', 25);
        $idRfvFiltro = $request->input('idRfv');

        $query = TTmpPlanificadore::withoutGlobalScope('idestatus')
            ->with(['rfv', 'cliente', 'supervisor']);

        $this->filterService->aplicarFiltrosFecha($query, $year, $month);
        $this->filterService->aplicarFiltrosSeguridad($query, $idRfvFiltro);

        if ($search) {
            $this->filterService->aplicarFiltroBusqueda($query, $search);
        }

        $query->orderBy('Fecha', 'asc')->orderBy('Hora', 'asc');

        try {
            $currentUserRole = $user->idgrupo_persona;

            return $query->paginate($size)->through(function ($item) use ($currentUserRole) {
                return [
                    'id' => $item->Id,
                    'idOperador' => $item->idOperador,
                    'idFabricante' => $item->idFabricante,
                    'idRFV' => $item->idRFV,
                    'nombre_rfv' => $item->rfv->nombre_completo_razon_social ?? 'No encontrado',
                    'idCliente' => $item->idCliente,
                    'nombre_cliente' => $item->cliente->nombre_completo_razon_social ?? 'No encontrado',
                    'Fecha' => $item->Fecha,
                    'Hora' => $item->Hora,
                    'idSupervisor' => $item->idSupervisor,
                    'nombre_supervisor' => $item->supervisor->nombre_completo_razon_social ?? 'No encontrado',
                    'idstatus' => $item->idstatus,
                    'idstatus' => $item->idstatus,
                    'userRole' => $currentUserRole,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error listando visitas temporales', [
                'error' => $e->getMessage(),
                'user_id' => $user->idPersona,
                'params' => $request->all()
            ]);
            
            return new LengthAwarePaginator([], 0, $size, $request->input('page', 1));
        }
    }

    // =============================================================================
    // CRUD DE VISITAS TEMPORALES
    // =============================================================================

    public function crearVisitaTemporal(array $data): TTmpPlanificadore
    {
        $user = Auth::user();

        $this->validationService->validarDatosBasicos($data);
        $this->validationService->validarFechaNoEsPasado($data['Fecha'], $data['Hora']);

        $datosParaCrear = $this->preparationService->prepararDatosCreacion($data, $user);
        $this->validationService->validarAsociacionClienteRfv($datosParaCrear['idRFV'], $datosParaCrear['idCliente']);
        $this->validationService->verificarDuplicado($datosParaCrear['idCliente'], $datosParaCrear['Fecha'], $data['Hora']);

        try {
            $visita = TTmpPlanificadore::create($datosParaCrear);

            Log::info('Visita temporal creada exitosamente', [
                'visita_id' => $visita->Id,
                'user_id' => $user->idPersona,
                'cliente_id' => $visita->idCliente,
                'fecha' => $visita->Fecha
            ]);

            return $visita;
        } catch (\Exception $e) {
            Log::error('ERROR CRÍTICO al crear visita temporal', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'user_id' => $user->idPersona,
                'data_enviada' => $data,
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Error al crear la visita temporal: ' . $e->getMessage());
        }
    }

    public function actualizarVisitaTemporal(int $id, array $data): ?TTmpPlanificadore
    {
        $user = Auth::user();
        $visita = TTmpPlanificadore::find($id);

        if (!$visita) {
            throw new \Exception('Visita temporal no encontrada.', 404);
        }

        if ($visita->idstatus != TTmpPlanificadore::ESTATUS_TEMPORAL) {
            throw new \Exception('No se puede editar esta visita.', 403);
        }

        if ($visita->idCreador != $user->idPersona) {
            throw new \Exception('No autorizado para editar esta visita', 403);
        }

        if (isset($data['Fecha']) && isset($data['Hora'])) {
            $this->validationService->validarFechaNoEsPasado($data['Fecha'], $data['Hora']);
        }

        $datosParaActualizar = $this->preparationService->extraerCamposActualizables($data);

        if (empty($datosParaActualizar)) {
            return $visita;
        }

        if (isset($datosParaActualizar['idCliente'])) {
            $this->validationService->validarAsociacionClienteRfv($visita->idRFV, $datosParaActualizar['idCliente']);
        }

        try {
            $visita->update($datosParaActualizar);

            Log::info('Visita temporal actualizada exitosamente', [
                'visita_id' => $id,
                'user_id' => $user->idPersona,
                'campos_actualizados' => array_keys($datosParaActualizar)
            ]);

            return $visita->fresh();
        } catch (\Exception $e) {
            Log::error('ERROR CRÍTICO al actualizar visita temporal', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'visita_id' => $id,
                'user_id' => $user->idPersona,
                'data_enviada' => $data,
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Error al actualizar la visita temporal: ' . $e->getMessage());
        }
    }

    public function eliminarVisitaTemporal(int $id): bool
    {
        $user = Auth::user();
        $visita = TTmpPlanificadore::find($id);

        if (!$visita) {
            throw new \Exception('Visita temporal no encontrada.', 404);
        }

        if ($visita->idstatus != TTmpPlanificadore::ESTATUS_TEMPORAL) {
            throw new \Exception('No se puede eliminar esta visita.', 403);
        }

        if ($visita->idCreador != $user->idPersona) {
            throw new \Exception('No autorizado para eliminar esta visita', 403);
        }

        try {
            $visita->delete();

            Log::info('Visita temporal eliminada exitosamente', [
                'visita_id' => $id,
                'user_id' => $user->idPersona,
                'cliente_id' => $visita->idCliente
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ERROR CRÍTICO al eliminar visita temporal', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'visita_id' => $id,
                'user_id' => $user->idPersona,
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Error al eliminar la visita: ' . $e->getMessage());
        }
    }


    // =============================================================================
    // MÉTODOS PRIVADOS - MAPEO
    // =============================================================================

    private function mapearVisitas($visitas, string $tipo, string $color, bool $puedeEditar, bool $puedeEliminar, bool $puedeProcesar): array
    {
        return $visitas->map(function($visita) use ($tipo, $color, $puedeEditar, $puedeEliminar, $puedeProcesar) {
            $fechaHora = Carbon::parse($visita->Fecha . ' ' . $visita->Hora);

            return [
                'id' => 'tmp_' . $visita->Id,
                'title' => $visita->cliente->nombre_completo_razon_social ?? 'Cliente no encontrado',
                'start' => $fechaHora->toISOString(),
                'end' => $fechaHora->copy()->addHour()->toISOString(),
                'tailwindColor' => $color,
                'metadata' => [
                    'tipo' => $tipo,
                    'cliente_id' => $visita->idCliente,
                    'rfv_id' => $visita->idRFV,
                    'nombre_cliente' => $visita->cliente->nombre_completo_razon_social ?? 'No encontrado',
                    'nombre_rfv' => $visita->rfv->nombre_completo_razon_social ?? 'No encontrado',
                    'fecha_original' => $visita->Fecha,
                    'hora_original' => $visita->Hora,
                    'puedeEditar' => $puedeEditar,
                    'puedeEliminar' => $puedeEliminar,
                    'puedeProcesar' => $puedeProcesar,
                    'id_original' => $visita->Id
                ],
            ];
        })->toArray();
    }

    private function mapearVisitasProcesadas($visitas): array
    {
        return $visitas->map(function($visita) {
            $fechaHora = Carbon::parse($visita->fecha_agenda . ' ' . $visita->hora);

            return [
                'id' => 'proc_' . $visita->idAgenda,
                'title' => $visita->cliente->nombre_completo_razon_social ?? 'Cliente no encontrado',
                'start' => $fechaHora->toISOString(),
                'end' => $fechaHora->copy()->addHour()->toISOString(),
                'tailwindColor' => self::COLOR_PROCESADA,
                'metadata' => [
                    'tipo' => 'procesada',
                    'cliente_id' => $visita->idCliente,
                    'rfv_id' => $visita->idRFV,
                    'nombre_cliente' => $visita->cliente->nombre_completo_razon_social ?? 'No encontrado',
                    'nombre_rfv' => 'RFV no disponible',
                    'fecha_original' => $visita->fecha_agenda,
                    'hora_original' => $visita->hora,
                    'puedeEditar' => false,
                    'puedeEliminar' => false,
                    'puedeProcesar' => false,
                    'id_original' => $visita->idAgenda
                ],
            ];
        })->toArray();
    }
}