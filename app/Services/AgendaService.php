<?php
// app/Services/AgendaService.php

namespace App\Services;

use App\Models\TPersona;
use App\Models\TPlanificadore;
use App\Models\TCiclo;
use App\Models\TFrecuenciaVisita;
use App\Models\TRankingCliente;
use App\Models\TEspecialidade;
use App\Models\RClienteRfv;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgendaService
{
    public function __construct(
        protected RepresentanteClienteService $representanteService,
        protected CompanyContextService $contextService,
    ) {}

    public function obtenerDatosAgenda(Request $request, ?string $idRfv = null): array
    {
        try {
            $user = Auth::user();
            $activeFabricante = $this->contextService->getActiveId();
            $idRfv = $idRfv ?? $request->input('idRfv');

            // Caso SIIF sin empresa seleccionada
            if ($user->idgrupo_persona === 'SIIF' && !$activeFabricante) {
                return [
                    'clientes' => new LengthAwarePaginator([], 0, 15),
                    'estadisticas' => $this->estadisticasVacias(),
                    'mensaje' => 'Seleccione una empresa en el panel superior.'
                ];
            }

            // Si el usuario es RFV solo ve sus datos
            if ($user->idgrupo_persona === 'RFV') {
                $idRfv = $user->idPersona;
            }

            // Lógica de Datos Específicos de un RFV
            if ($idRfv) {
                // Validación de seguridad (¿Este RFV pertenece a la empresa activa?)
                if (!$this->representanteService->tienePermisosRfv($idRfv)) {
                    return [
                        'clientes' => new LengthAwarePaginator([], 0, 15),
                        'estadisticas' => $this->estadisticasVacias()
                    ];
                }

                return [
                    'clientes' => $this->obtenerClientesPaginados($request, $idRfv),
                    'estadisticas' => $this->calcularEstadisticas($idRfv, $user)
                ];
            }

            // Lógica de Estadísticas Generales (SIIF con empresa o GRT/SUP)
            if ($activeFabricante) {
                return [
                    'clientes' => $this->obtenerClientesPaginadosEmpresa($request, $activeFabricante, $request->input('idCliente')),
                    'estadisticas' => $this->calcularEstadisticasGeneralesEmpresa($activeFabricante)
                ];
            }

            return ['clientes' => new LengthAwarePaginator([], 0, 15), 'estadisticas' => $this->estadisticasVacias()];

        } catch (\Exception $e) {
            Log::error('Error en obtenerDatosAgenda: ' . $e->getMessage());
            return ['clientes' => new LengthAwarePaginator([], 0, 15), 'estadisticas' => $this->estadisticasVacias()];
        }
    }

    /**
     * Calcula las estadísticas de visitas del ciclo
     */
    private function calcularEstadisticas(string $idRfv, $user): array
    {
        try {
            $dhabiles = null;
            $ciclo = 0;
            $visitados = 0;

            // Obtener información del RFV
            $rfv = TPersona::find($idRfv);


            
            // Obtener días hábiles del ciclo del RFV
            if ($rfv && $rfv->idciclos) {
                $cicloData = TCiclo::withoutGlobalScopes()->find($rfv->idciclos);
                if ($cicloData) {
                    $descripcion = strtoupper($cicloData->descripcion_ciclos);
                    $dhabiles = ($descripcion === 'MENSUAL') ? 20 : null;
                }
            }

            // Obtener todos los clientes para calcular estadísticas
            $clientes = $this->obtenerClientesBase($idRfv);



            foreach ($clientes as $cliente) {
                // Contar visitas realizadas
                $visitasRealizadas = TPlanificadore::withoutGlobalScopes()
                    ->where([
                        'idRFV' => $idRfv,
                        'idCliente' => $cliente->idPersona,
                        'idestatus' => 2
                    ])
                    ->count();

                if ($visitasRealizadas > 0) {
                    $visitados++;
                }

                // Sumar frecuencia esperada
                if ($cliente->idfrecuencia) {
                    $frecuencia = TFrecuenciaVisita::withoutGlobalScopes()->find($cliente->idfrecuencia);
                    if ($frecuencia && !empty($frecuencia->descripcion_frecuencia_visitas)) {
                        $valorFrecuencia = (float) substr($frecuencia->descripcion_frecuencia_visitas, 0, 1);
                        $ciclo += $valorFrecuencia;
                    }
                }
            }

            // Calcular cobertura respecto al total de clientes
            $totalClientes = is_countable($clientes) ? count($clientes) : 0;
            $cobertura = ($totalClientes > 0) ? (($visitados / $totalClientes) * 100) : 0;

            Log::debug('calcularEstadisticas - totals', [
                'idRfv' => $idRfv,
                'ciclo' => $ciclo,
                'dhabiles' => $dhabiles,
                'visitados' => $visitados,
                'totalClientes' => $totalClientes,
                'cobertura' => round($cobertura, 2)
            ]);

            // Visitas diarias basadas en el ciclo (si aplica)
            $visitasDiarias = ($dhabiles && $dhabiles > 0 && $ciclo > 0) ? ($ciclo / $dhabiles) : null;

            return [
                'ciclo' => $ciclo,
                'dhabiles' => $dhabiles,
                'visitados' => $visitados,
                'cobertura' => round($cobertura, 2),
                'visitasDiarias' => $visitasDiarias ? round($visitasDiarias, 2) : null
            ];

        } catch (\Exception $e) {
            Log::error('Error calculando estadísticas:', [
                'message' => $e->getMessage(),
                'idRfv' => $idRfv
            ]);
            return $this->estadisticasVacias();
        }
    }

    /**
     * Obtiene los clientes paginados con toda su información
     */
    private function obtenerClientesPaginados(Request $request, string $idRfv, ?string $idCliente = null): LengthAwarePaginator
    {
        $size = $request->input('size', 15);
        $page = $request->input('page', 1);

        $query = $this->obtenerClientesQuery($idRfv);

        // Filtro por cliente específico
        if ($idCliente) {
            $query->where('t_personas.idPersona', $idCliente);
        }

        return $query
            ->orderBy('t_personas.nombre_completo_razon_social')
            ->paginate($size, ['*'], 'page', $page)
            ->withQueryString()
            ->through(function ($cliente) use ($idRfv) {
                return $this->formatearCliente($cliente, $idRfv);
            });
    }

    /**
     * Obtiene el query base de clientes usando la tabla relacional r_cliente_rfv
     */
    private function obtenerClientesQuery(string $idRfv)
    {
        return TPersona::whereHas('clientesRfv', function ($query) use ($idRfv) {
            $query->where('id_RFV', $idRfv);
        })
        ->whereNotNull('nombre_completo_razon_social')
        ->where('nombre_completo_razon_social', '<>', '');
    }

    /**
     * Obtiene clientes sin paginación para cálculos
     */
    private function obtenerClientesBase(string $idRfv)
    {
        return TPersona::whereHas('clientesRfv', function ($query) use ($idRfv) {
            $query->where('id_RFV', $idRfv);
        })
        ->whereNotNull('nombre_completo_razon_social')
        ->where('nombre_completo_razon_social', '<>', '')
        ->get();
    }

    /**
     * Retorna estadísticas vacías por defecto
     */
    private function estadisticasVacias(): array
    {
        return [
            'ciclo' => 0,
            'dhabiles' => null,
            'visitados' => 0,
            'cobertura' => 0,
            'visitasDiarias' => null
        ];
    }

    /**
     * Obtiene datos para exportar a Excel (sin paginación)
     */
    public function obtenerDatosParaExcel(Request $request, ?string $idRfv = null): array
        {
            $idRfv = $idRfv ?? $request->input('idRfv');
            if (!$idRfv || !$this->representanteService->tienePermisosRfv($idRfv)) return [];

            $query = $this->obtenerClientesQuery($idRfv);
            if ($request->filled('idCliente')) {
                $query->where('t_personas.idPersona', $request->idCliente);
            }

            return $query->get()->map(fn($c) => $this->formatearParaExcel($c, $idRfv))->toArray();
        }

    public function obtenerDatosGeneralesEmpresa(string $idFabricante): array
    {
        try {
            $rfvs = TPersona::withoutGlobalScopes()
                ->where('idgrupo_persona', 'RFV')
                ->where('idFabricante', $idFabricante)
                ->get(['idPersona', 'nombre_completo_razon_social']);

            $datosTotales = [];

            foreach ($rfvs as $rfv) {
                $clientes = $this->obtenerClientesBase($rfv->idPersona);

                foreach ($clientes as $cliente) {
                    $datosTotales[] = $this->formatearParaExcel($cliente, $rfv->idPersona);
                }
            }

            return $datosTotales;

        } catch (\Exception $e) {
            Log::error('Error al preparar datos generales para Excel: ' . $e->getMessage());
            return [];
        }
    }

    private function formatearParaExcel($cliente, $idRfv)
    {
        $formatted = $this->formatearCliente($cliente, $idRfv);
        return [
            'ID RFV' => $formatted['idRFV'],
            'Nombre' => $formatted['nombre'],
            'Ranking' => $formatted['ranking'],
            'Actividad' => $formatted['actividad'],
            'Días Visita' => collect($formatted['dias_visita'])->pluck('descripcion')->join(', '),
            'Horarios' => collect($formatted['horarios'])->pluck('descripcion')->join(', '),
            'Visitas' => $formatted['visitado'] . ' / ' . $formatted['frecuencia']
        ];
    }

    /**
     * Formatea un cliente con toda su información
     * VERSIÓN CORREGIDA - Mantiene el formato esperado por el frontend
     */
    private function formatearCliente($cliente, string $idRfv): array
    {
        $user = Auth::user();
        $cliente->load(['Dias', 'Horarios']);
        
        // Obtener el ID del RFV 
        $nombreRfv = $idRfv;

        // Obtener ranking
        $ranking = null;
        if ($cliente->idranking) {
            $ranking = TRankingCliente::withoutGlobalScopes()->find($cliente->idranking);
        }
        
        // Obtener actividad
        $actividad = null;
        if ($cliente->idactividad_negocio) {
            $actividad = TEspecialidade::withoutGlobalScopes()->find($cliente->idactividad_negocio);
        }

        // Obtener frecuencia
        $frecuencia = null;
        $frecuenciaChar = '0';
        if ($cliente->idfrecuencia) {
            $frecuencia = TFrecuenciaVisita::withoutGlobalScopes()->find($cliente->idfrecuencia);
            if ($frecuencia && !empty($frecuencia->descripcion_frecuencia_visitas)) {
                $frecuenciaChar = substr($frecuencia->descripcion_frecuencia_visitas, 0, 1);
            }
        }

        // Contar visitas realizadas
        $visitasRealizadas = TPlanificadore::withoutGlobalScopes()
            ->where([
                'idRFV' => $idRfv,
                'idCliente' => $cliente->idPersona,
                'idestatus' => 2
            ])
            ->count();

        // Obtener días de visita
        $diasVisita = $cliente->Dias->map(function ($dia) {
            return [
                'id' => $dia->iddias_visita,
                'descripcion' => $dia->descripcion_dias_visita
            ];
        });

        // Obtener horarios
        $horarios = $cliente->Horarios->map(function ($horario) {
            return [
                'id' => $horario->idhorarios,
                'descripcion' => $horario->descripcion_horarios
            ];
        });

        return [
            'id' => $cliente->idPersona,
            'idRFV' => $nombreRfv,
            'nombre' => $cliente->nombre_completo_razon_social,
            'ranking' => $ranking ? $ranking->descripcion_ranking_cliente : 'N/A',
            'actividad' => $actividad ? $actividad->descripcion_especialidad : 'N/A',
            'frecuencia' => $frecuenciaChar,
            'visitado' => $visitasRealizadas,
            'dias_visita' => $diasVisita,
            'horarios' => $horarios,
            'direccion' => $cliente->direccion ?? null,
            'telefono' => $cliente->telefono ?? null
        ];
    }


    /**
     * Calcula las estadísticas generales de una empresa (para GRT/SUP sin RFV seleccionado)
     * @param string $idFabricante
     * @return array
     */
    public function calcularEstadisticasGeneralesEmpresa(string $idFabricante): array
    {
        try {
            // Obtener IDs de todos los RFVs de la empresa sin filtros globales
            $rfvs = TPersona::withoutGlobalScopes()
                ->where('idgrupo_persona', 'RFV') 
                ->where('idFabricante', $idFabricante)
                ->pluck('idPersona')
                ->toArray();

            if (empty($rfvs)) {
                Log::warning("No se encontraron RFVs para el fabricante: $idFabricante");
                return $this->estadisticasVacias();
            }

            $cicloTotal = 0;
            $visitadosTotal = 0;
            $dhabiles = 20;
            $totalClientesUnicos = 0;

            // Obtener todos los clientes asociados a esos RFVs
            $clientesIds = RClienteRfv::whereIn('id_RFV', $rfvs)->pluck('id_cliente')->unique();
            $totalClientesUnicos = $clientesIds->count();

            if ($totalClientesUnicos === 0) return $this->estadisticasVacias();

            // Consultar datos de los clientes (Frecuencias)
            $clientesData = TPersona::withoutGlobalScopes()
                ->whereIn('idPersona', $clientesIds)
                ->get(['idPersona', 'idfrecuencia']);

            foreach ($clientesData as $cliente) {
                // Sumar frecuencia esperada
                if ($cliente->idfrecuencia) {
                    $frecuencia = TFrecuenciaVisita::withoutGlobalScopes()->find($cliente->idfrecuencia);
                    if ($frecuencia && !empty($frecuencia->descripcion_frecuencia_visitas)) {
                        // Extraer el primer número (ej: "4 visitas" -> 4)
                        $valorFrecuencia = (float) filter_var($frecuencia->descripcion_frecuencia_visitas, FILTER_SANITIZE_NUMBER_INT);
                        $cicloTotal += ($valorFrecuencia > 0) ? $valorFrecuencia : 0;
                    }
                }
            }

            // Contar visitas reales realizadas por este grupo de RFVs en este ciclo
            $visitadosTotal = TPlanificadore::withoutGlobalScopes()
                ->whereIn('idRFV', $rfvs)
                ->whereIn('idCliente', $clientesIds)
                ->where('idestatus', 2)
                ->distinct('idCliente') // clientes visitados, no total de visitas
                ->count('idCliente');

            // Cálculos finales
            $cobertura = ($totalClientesUnicos > 0) ? (($visitadosTotal / $totalClientesUnicos) * 100) : 0;
            $visitasDiarias = ($dhabiles > 0 && $cicloTotal > 0) ? ($cicloTotal / $dhabiles) : 0;

            return [
                'ciclo' => round($cicloTotal, 2),
                'dhabiles' => $dhabiles,
                'visitados' => $visitadosTotal,
                'cobertura' => round($cobertura, 2),
                'visitasDiarias' => round($visitasDiarias, 2)
            ];

        } catch (\Exception $e) {
            Log::error('Error calculando estadísticas generales:', ['msg' => $e->getMessage()]);
            return $this->estadisticasVacias();
        }
    }
    /**
     * Obtiene clientes paginados de toda la empresa (para GRT/SUP sin RFV)
     */
    private function obtenerClientesPaginadosEmpresa(Request $request, string $idFabricante, ?string $idCliente = null): LengthAwarePaginator
    {
        $size = $request->input('size', 15);
        $page = $request->input('page', 1);
        $rfvs = TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $idFabricante)
            ->pluck('idPersona')
            ->toArray();

        $query = TPersona::withoutGlobalScopes()
            ->whereHas('clientesRfv', function ($q) use ($rfvs) {
                $q->whereIn('id_RFV', $rfvs);
            })
            ->whereNotNull('nombre_completo_razon_social');

        if ($idCliente) {
            $query->where('idPersona', $idCliente);
        }

        return $query->orderBy('nombre_completo_razon_social')
            ->paginate($size, ['*'], 'page', $page)
            ->withQueryString()
            ->through(function ($cliente) use ($rfvs) {
                // Buscamos el RFV para el formateo
                $relacion = RClienteRfv::where('id_cliente', $cliente->idPersona)
                    ->whereIn('id_RFV', $rfvs)
                    ->first();
                return $this->formatearCliente($cliente, $relacion->id_RFV ?? $rfvs[0]);
            });
    }
}

