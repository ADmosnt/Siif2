<?php

namespace App\Http\Controllers\Reportes;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TPlanificadore;
use App\Models\TTmpPlanificadore;
use App\Models\TProducto;
use App\Models\TActividadesRepresentante;
use App\Services\ReporteDataService;
use App\Services\TmpPlanificadorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Exceptions\VisitaTemporalNoEncontradaException;

class ProcesarReporteController extends Controller
{
    protected $reporteDataService;
    protected $tmpPlanificadorService;

    public function __construct(ReporteDataService $reporteDataService, TmpPlanificadorService $tmpPlanificadorService)
    {
        $this->reporteDataService = $reporteDataService;
        $this->tmpPlanificadorService = $tmpPlanificadorService;
    }

    public function new(Request $request)
    {
        $mensaje = null;
        $tipoMensaje = null;
        $rutaRedireccion = null;

        // Validaciones iniciales
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|numeric',
            'idcliente' => 'required|string',
            'incidentes' => 'required|numeric',
            'comentario' => 'nullable|string|max:80',
            'muestras' => 'array',
            'rfv_id' => 'required|string',
            'visita_temporal_id' => 'nullable|numeric|exists:t_tmp_planificadores,Id',
        ]);

        if ($validator->fails()) {
            Log::warning('Fallo de validación al procesar reporte:', $validator->errors()->toArray());
            $mensaje = 'Error de validación en los datos ingresados.';
            $tipoMensaje = 'error';
            $rutaRedireccion = 'back';
        } else {
            $esVisitaAgendada = !is_null($request->input('visita_temporal_id'));

            try {
                $idFabricante = $this->reporteDataService->obtenerIdFabricante($request->input('rfv_id'));

                if (is_null($idFabricante)) {
                    Log::error('Error crítico: ID de Fabricante no pudo ser obtenido para el RFV.', ['rfv_id' => $request->input('rfv_id'), 'user_id' => Auth::id()]);
                    $mensaje = 'Error: No se pudo determinar el fabricante para el RFV seleccionado. Por favor, contacte a soporte.';
                    $tipoMensaje = 'error';
                    $rutaRedireccion = 'back';
                } else {
                    $date = now()->format('Y-m-d H:i:s');
                    $user = Auth::user();

                    $datosVisitaTemporal = null;
                    $idTmpVisita = null;

                    if ($esVisitaAgendada) {
                        $idTmpVisita = $request->input('visita_temporal_id');

                        $visitaTemporal = TTmpPlanificadore::find($idTmpVisita);

                        if (!$visitaTemporal) {
                            Log::error('Visita temporal no encontrada para procesar.', ['id_tmp_visita' => $idTmpVisita, 'user_id' => $user->idPersona]);
                            $mensaje = 'Error: La visita temporal a procesar no existe o ya ha sido procesada.';
                            $tipoMensaje = 'error';
                            $rutaRedireccion = 'back';
                        } else {
                            $puedeProcesar = false;
                            if ($visitaTemporal->idRFV == $user->idPersona) {
                                $puedeProcesar = true;
                            } elseif (in_array($user->idgrupo_persona, ['GRT', 'SUP']) && $visitaTemporal->idCreador == $user->idPersona) {
                                $puedeProcesar = true;
                            }

                            if (!$puedeProcesar) {
                                Log::error('Usuario no autorizado para procesar esta visita temporal.', [
                                    'id_tmp_visita' => $idTmpVisita,
                                    'user_id' => $user->idPersona,
                                    'grupo_usuario' => $user->idgrupo_persona,
                                    'idRFV_visita' => $visitaTemporal->idRFV,
                                    'idCreador_visita' => $visitaTemporal->idCreador,
                                ]);
                                $mensaje = 'Error: No está autorizado para procesar esta visita temporal.';
                                $tipoMensaje = 'error';
                                $rutaRedireccion = 'back';
                            } else {
                                $datosVisitaTemporal = [
                                    'fecha_agenda' => $visitaTemporal->Fecha,
                                    'hora' => $visitaTemporal->Hora,
                                    'observacion_agenda' => $visitaTemporal->observacion_agenda ?? '',
                                    'idSupervisor' => $visitaTemporal->idSupervisor,
                                    'idCliente' => $visitaTemporal->idCliente,
                                    'idRFV' => $visitaTemporal->idRFV,
                                ];

                                if ($request->input('idcliente') !== $datosVisitaTemporal['idCliente'] || $request->input('rfv_id') !== $datosVisitaTemporal['idRFV']) {
                                    Log::error('Cliente o RFV del formulario no coincide con la visita temporal.', [
                                        'id_tmp_visita' => $idTmpVisita,
                                        'form_cliente' => $request->input('idcliente'),
                                        'form_rfv' => $request->input('rfv_id'),
                                        'tmp_cliente' => $datosVisitaTemporal['idCliente'],
                                        'tmp_rfv' => $datosVisitaTemporal['idRFV'],
                                    ]);
                                    $mensaje = 'Error: Datos del formulario no coinciden con la visita temporal.';
                                    $tipoMensaje = 'error';
                                    $rutaRedireccion = 'back';
                                }
                            }
                        }
                    }

                    if ($mensaje === null) {
                        // --- TRANSACCIÓN ---
                        $resultadoTransaccion = DB::transaction(function () use ($request, $idFabricante, $date, $user, $esVisitaAgendada, $datosVisitaTemporal, $idTmpVisita) {
                            // --- Crear Actividad ---
                            $actividad = TActividadesRepresentante::create([
                                'idOperador' => $user->idOperador,
                                'idFabricante' => $idFabricante,
                                'idtipo_actividades' => $request->input('tipo'),
                                'idPersona' => $user->idPersona,
                                'idRFV' => $request->input('rfv_id'),
                                'idCliente' => $request->input('idcliente'),
                                'idtipo_incidentes' => $request->input('incidentes'),
                                'fecha_actividad' => $date,
                                'observaciones_cliente' => $request->input('comentario'),
                                'idestatus' => '1'
                            ]);

                            $idReporteCreado = $actividad->idreporte;

                            $idPlanificacionCreada = null;
                            if ($esVisitaAgendada) {
                                // --- Crear Planificación ---
                                $planificacion = TPlanificadore::create([
                                    'idFabricante' => $idFabricante,
                                    'idOperador' => $user->idOperador,
                                    'idRFV' => $datosVisitaTemporal['idRFV'],
                                    'idCliente' => $datosVisitaTemporal['idCliente'],
                                    'fecha_agenda' => $datosVisitaTemporal['fecha_agenda'],
                                    'hora' => $datosVisitaTemporal['hora'],
                                    'observacion_agenda' => $request->input('comentario') ?? $datosVisitaTemporal['observacion_agenda'],
                                    'idSupervisor' => $datosVisitaTemporal['idSupervisor'],
                                    'idestatus' => 2,
                                    'idreporte' => $idReporteCreado,
                                ]);

                                $idPlanificacionCreada = $planificacion->idAgenda;

                                // --- Eliminar Visita Temporal ---
                                $visitaTemporalAEliminar = TTmpPlanificadore::find($idTmpVisita);
                                if (!$visitaTemporalAEliminar) {
                                    throw new VisitaTemporalNoEncontradaException($idTmpVisita);
                                }
                                $visitaTemporalAEliminar->delete();

                                Log::info('Visita temporal procesada, confirmada y eliminada:', [
                                    'id_tmp_visita_eliminada' => $idTmpVisita,
                                    'id_planificacion_creada' => $idPlanificacionCreada,
                                    'id_actividad_creada' => $idReporteCreado,
                                ]);
                            }

                            // --- Procesar Muestras ---
                            $muestrasEntregadas = [];
                            if ($request->has('muestras') && is_array($request->input('muestras'))) {
                                foreach ($request->input('muestras') as $muestra) {
                                    $producto = TProducto::find($muestra['idproducto']);

                                    if ($producto && $producto->cantidad_producto_existente >= $muestra['cantidad']) {
                                        $producto->reducirInventario($muestra['cantidad']);

                                        $actividad->Muestras()->attach($muestra['idproducto'], [
                                            'cantidad' => $muestra['cantidad'],
                                            'idOperador' => $user->idOperador,
                                            'idFabricante' => $idFabricante
                                        ]);

                                        $muestrasEntregadas[] = [
                                            'codigo' => $muestra['idproducto'],
                                            'cantidad' => $muestra['cantidad'],
                                            'lote' => $muestra['lote'] ?? 'N/A'
                                        ];
                                    } else {
                                        Log::warning('Muestra no procesada (producto no encontrado o stock insuficiente):', [
                                            'idproducto_solicitado' => $muestra['idproducto'],
                                            'cantidad_solicitada' => $muestra['cantidad'],
                                            'stock_disponible' => $producto->cantidad_producto_existente ?? 'N/A',
                                            'reporte_id' => $idReporteCreado
                                        ]);
                                    }
                                }
                            }

                            return [ // Devolver los resultados de la transacción
                                'id_actividad_creada' => $idReporteCreado,
                                'id_planificacion_creada' => $idPlanificacionCreada,
                                'muestras_entregadas' => $muestrasEntregadas,
                                'esVisitaAgendada' => $esVisitaAgendada,
                                'idTmpVisita' => $idTmpVisita,
                            ];
                        });

                        // Si la transacción se completó con éxito
                        $logData = [
                            'id_actividad_creada' => $resultadoTransaccion['id_actividad_creada'],
                            'cliente_id' => $request->input('idcliente'),
                            'rfv_id' => $request->input('rfv_id'),
                            'tipo_actividad_id' => $request->input('tipo'),
                            'tipo_incidente_id' => $request->input('incidentes'),
                            'comentario' => $request->input('comentario'),
                            'id_fabricante' => $idFabricante,
                            'id_operador' => $user->idOperador,
                            'muestras_entregadas' => $resultadoTransaccion['muestras_entregadas'],
                        ];

                        if ($resultadoTransaccion['esVisitaAgendada']) {
                            $logData['id_planificacion_creada'] = $resultadoTransaccion['id_planificacion_creada'];
                            $logData['id_visita_temporal_procesada'] = $resultadoTransaccion['idTmpVisita'];
                        } else {
                            $logData['flujo'] = 'Visita No Agendada';
                        }

                        Log::info('Reporte de actividad procesado con éxito (transacción completada):', $logData);

                        $mensaje = $resultadoTransaccion['esVisitaAgendada'] ? 'Visita agendada procesada con éxito.' : 'Actividad reportada con éxito.';
                        $tipoMensaje = 'success';
                        $rutaRedireccion = $resultadoTransaccion['esVisitaAgendada'] ? 'tmp_planificaciones.index' : 'nuevo-reporte.index';
                    }
                }
            } catch (VisitaTemporalNoEncontradaException $e) {
                Log::warning('Intento de procesar visita temporal ya eliminada:', [
                    'message' => $e->getMessage(),
                    'visita_temporal_id' => $e->getCode() !== 0 ? $e->getCode() : $request->input('visita_temporal_id'),
                    'request_data' => $request->all(),
                    'user_id' => Auth::id(),
                ]);
                $mensaje = $e->getMessage();
                $tipoMensaje = 'error';
                $rutaRedireccion = 'back';

            } catch (\Exception $e) {
                // Captura errores generales 
                Log::error('Error crítico al procesar reporte de actividad (transacción fallida o error general):', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all(),
                    'user_id' => Auth::id(),
                    'rfv_id' => $request->input('rfv_id') ?? 'N/A',
                    'visita_temporal_id' => $request->input('visita_temporal_id') ?? 'N/A',
                ]);
                $mensaje = 'Ha ocurrido un error inesperado, por favor inténtelo más tarde.';
                $tipoMensaje = 'error';
                $rutaRedireccion = 'back';
            }
        }

        // --- REDIRECCIÓN ÚNICA ---
        if ($tipoMensaje === 'success' && $rutaRedireccion && $rutaRedireccion !== 'back') {
            
            try {
                return redirect()->route($rutaRedireccion)->with($tipoMensaje, $mensaje);
            } catch (\Exception $e) {
                // Si falla la redirección de éxito, loguear y redirigir atrás con error
                Log::error('Error redirigiendo tras procesamiento exitoso:', [
                    'message' => $e->getMessage(),
                    'ruta_intento' => $rutaRedireccion,
                ]);
                // Mostrar mensaje de éxito original como error porque la redirección falló
                return redirect()->back()->with('error', $mensaje . ' (Además, hubo un problema al redirigir.)');
            }
        } else {

            $ruta = $rutaRedireccion === 'back' ? redirect()->back() : redirect()->route($rutaRedireccion);
            return $ruta->with($tipoMensaje, $mensaje);
        }
    }
}