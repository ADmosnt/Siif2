<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ActividadResource;
use App\Http\Resources\Api\ActividadDetalleResource;
use App\Models\TActividadesRepresentante;
use App\Models\TPlanificadore;
use App\Models\TTmpPlanificadore;
use App\Models\TProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ActividadController extends Controller
{
    /**
     * Lista las últimas 10 actividades del usuario autenticado.
     */
    public function index()
    {
        $user = Auth::user();

        $actividades = TActividadesRepresentante::where('idRFV', $user->idPersona)
            ->orderBy('idreporte', 'desc')
            ->take(10)
            ->get();

        return ActividadResource::collection($actividades);
    }

    /**
     * Crear un reporte NUEVO (visita no planificada).
     * Equivalente a ReporteController@new de Siif1.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|numeric',
            'idcliente' => 'required|string',
            'incidentes' => 'required|numeric',
            'muestras' => 'array',
            'muestras.*.idproducto' => 'required_with:muestras|string',
            'muestras.*.cantidad' => 'required_with:muestras|integer|min:1',
            'comentario' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'firma' => 'nullable|string',
            'fecha' => 'nullable|date',
        ], [
            'required' => ':attribute es requerido',
            'numeric' => ':attribute debe ser numérico',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $date = $request->fecha ? Carbon::parse($request->fecha) : Carbon::now();

        try {
            $resultado = DB::transaction(function () use ($request, $user, $date) {
                // Crear planificación automática
                $planificacion = TPlanificadore::create([
                    'idFabricante' => $user->idFabricante,
                    'idOperador' => $user->idOperador,
                    'idRFV' => $user->idPersona,
                    'idCliente' => $request->idcliente,
                    'fecha_agenda' => $date->format('Y-m-d'),
                    'hora' => Carbon::now()->format('H:i:s'),
                    'observacion_agenda' => $request->comentario ?? '',
                    'idSupervisor' => $user->idsupervisor,
                    'idestatus' => 2,
                ]);

                // Crear actividad
                $actividad = TActividadesRepresentante::create([
                    'idOperador' => $user->idOperador,
                    'idFabricante' => $user->idFabricante,
                    'idtipo_actividades' => $request->tipo,
                    'idPersona' => $user->idPersona,
                    'idRFV' => $user->idPersona,
                    'idCliente' => $request->idcliente,
                    'coordenadas_l' => $request->lat,
                    'coordenadas_a' => $request->long,
                    'idtipo_incidentes' => $request->incidentes,
                    'fecha_actividad' => $date,
                    'Firma_cliente' => $request->firma,
                    'observaciones_cliente' => $request->comentario,
                    'idestatus' => '1',
                ]);

                $planificacion->idreporte = $actividad->idreporte;
                $planificacion->save();

                // Procesar muestras
                if ($request->has('muestras') && is_array($request->muestras)) {
                    foreach ($request->muestras as $muestra) {
                        $producto = TProducto::find($muestra['idproducto']);
                        if ($producto && $producto->cantidad_producto_existente >= $muestra['cantidad']) {
                            $producto->reducirInventario($muestra['cantidad']);
                            $actividad->Muestras()->attach($muestra['idproducto'], [
                                'cantidad' => $muestra['cantidad'],
                                'idOperador' => $user->idOperador,
                                'idFabricante' => $user->idFabricante,
                            ]);
                        }
                    }
                }

                return $actividad;
            });

            return response()->json(['success' => 'Actividad reportada', 'idreporte' => $resultado->idreporte], 200);

        } catch (\Exception $e) {
            Log::error('Error al crear reporte API', ['message' => $e->getMessage(), 'user' => $user->idPersona]);
            return response()->json(['error' => 'Error al procesar el reporte'], 500);
        }
    }

    /**
     * Crear reporte desde una visita temporal agendada.
     */
    public function storeFromAgenda(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|numeric',
            'idcliente' => 'required|string',
            'incidentes' => 'required|numeric',
            'visita_temporal_id' => 'required|numeric',
            'muestras' => 'array',
            'muestras.*.idproducto' => 'required_with:muestras|string',
            'muestras.*.cantidad' => 'required_with:muestras|integer|min:1',
            'comentario' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'firma' => 'nullable|string',
        ], [
            'required' => ':attribute es requerido',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        // Verificar que la visita temporal existe y pertenece al usuario
        $visitaTemporal = TTmpPlanificadore::find($request->visita_temporal_id);
        if (!$visitaTemporal) {
            return response()->json(['error' => 'Visita temporal no encontrada'], 404);
        }

        // Verificar permisos
        $puedeProcesar = ($visitaTemporal->idRFV == $user->idPersona)
            || (in_array($user->idgrupo_persona, ['GRT', 'SUP']) && $visitaTemporal->idCreador == $user->idPersona);

        if (!$puedeProcesar) {
            return response()->json(['error' => 'No autorizado para procesar esta visita'], 403);
        }

        try {
            $resultado = DB::transaction(function () use ($request, $user, $visitaTemporal) {
                // Crear actividad
                $actividad = TActividadesRepresentante::create([
                    'idOperador' => $user->idOperador,
                    'idFabricante' => $user->idFabricante,
                    'idtipo_actividades' => $request->tipo,
                    'idPersona' => $user->idPersona,
                    'idRFV' => $visitaTemporal->idRFV,
                    'idCliente' => $request->idcliente,
                    'coordenadas_l' => $request->lat,
                    'coordenadas_a' => $request->long,
                    'idtipo_incidentes' => $request->incidentes,
                    'fecha_actividad' => now(),
                    'Firma_cliente' => $request->firma,
                    'observaciones_cliente' => $request->comentario,
                    'idestatus' => '1',
                ]);

                // Crear planificación confirmada
                $planificacion = TPlanificadore::create([
                    'idFabricante' => $user->idFabricante,
                    'idOperador' => $user->idOperador,
                    'idRFV' => $visitaTemporal->idRFV,
                    'idCliente' => $visitaTemporal->idCliente,
                    'fecha_agenda' => $visitaTemporal->Fecha,
                    'hora' => $visitaTemporal->Hora,
                    'observacion_agenda' => $request->comentario ?? '',
                    'idSupervisor' => $visitaTemporal->idSupervisor,
                    'idestatus' => 2,
                    'idreporte' => $actividad->idreporte,
                ]);

                // Eliminar visita temporal
                $visitaTemporal->delete();

                // Procesar muestras
                if ($request->has('muestras') && is_array($request->muestras)) {
                    foreach ($request->muestras as $muestra) {
                        $producto = TProducto::find($muestra['idproducto']);
                        if ($producto && $producto->cantidad_producto_existente >= $muestra['cantidad']) {
                            $producto->reducirInventario($muestra['cantidad']);
                            $actividad->Muestras()->attach($muestra['idproducto'], [
                                'cantidad' => $muestra['cantidad'],
                                'idOperador' => $user->idOperador,
                                'idFabricante' => $user->idFabricante,
                            ]);
                        }
                    }
                }

                return $actividad;
            });

            return response()->json(['success' => 'Visita agendada procesada', 'idreporte' => $resultado->idreporte], 200);

        } catch (\Exception $e) {
            Log::error('Error al procesar visita agendada API', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error al procesar la visita'], 500);
        }
    }

    /**
     * Guardar firma digital del cliente.
     */
    public function saveFirma(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idreporte' => 'required|numeric',
            'firma' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $actividad = TActividadesRepresentante::find($request->idreporte);
        if (!$actividad) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        $actividad->Firma_cliente = $request->firma;
        $actividad->save();

        return response()->json(['success' => 'Firma guardada correctamente']);
    }

    /**
     * Detalle completo de una actividad.
     */
    public function detalle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idreporte' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $actividad = TActividadesRepresentante::with(['representante', 'cliente', 'tipoActividad', 'incidente', 'Muestras'])
            ->find($request->idreporte);

        if (!$actividad) {
            return response()->json(['error' => 'Reporte no encontrado'], 404);
        }

        return new ActividadDetalleResource($actividad);
    }
}
