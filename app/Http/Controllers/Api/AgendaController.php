<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CitaResource;
use App\Http\Resources\Api\TmpCitaResource;
use App\Models\TPersona;
use App\Models\TPlanificadore;
use App\Models\TTmpPlanificadore;
use App\Models\TDiasVisita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AgendaController extends Controller
{
    /**
     * Lista las citas confirmadas del usuario.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->esRepresentante()) {
            $citas = TPlanificadore::withoutGlobalScopes()
                ->where('idRFV', $user->idPersona)
                ->where('idOperador', $user->idOperador)
                ->where('idFabricante', $user->idFabricante)
                ->orderBy('fecha_agenda', 'desc')
                ->take(20)
                ->get();
        } else {
            $citas = TPlanificadore::withoutGlobalScopes()
                ->where('idSupervisor', $user->idPersona)
                ->where('idOperador', $user->idOperador)
                ->where('idFabricante', $user->idFabricante)
                ->orderBy('fecha_agenda', 'desc')
                ->take(20)
                ->get();
        }

        return CitaResource::collection($citas);
    }

    /**
     * Lista visitas temporales pendientes del usuario.
     */
    public function citasPendientes()
    {
        $user = Auth::user();

        $query = TTmpPlanificadore::withoutGlobalScopes()
            ->where('idstatus', TTmpPlanificadore::ESTATUS_TEMPORAL)
            ->where('idOperador', $user->idOperador)
            ->where('idFabricante', $user->idFabricante);

        if ($user->esRepresentante()) {
            $query->where('idRFV', $user->idPersona);
        } elseif ($user->esSupervisor()) {
            $query->where(function ($q) use ($user) {
                $q->where('idRFV', $user->idPersona)
                  ->orWhere('idCreador', $user->idPersona);
            });
        }

        $citas = $query->orderBy('Fecha', 'asc')->get();

        return TmpCitaResource::collection($citas);
    }

    /**
     * Crear una nueva visita temporal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idCliente' => 'required|string',
            'idRFV' => 'nullable|string',
            'fecha' => 'required|date',
            'hora' => 'required',
            'estatus_visita' => 'nullable|integer',
        ], [
            'required' => 'El :attribute es requerido',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $idRFV = $request->idRFV ?? $user->idPersona;

        $cita = TTmpPlanificadore::create([
            'idOperador' => $user->idOperador,
            'idFabricante' => $user->idFabricante,
            'idRFV' => $idRFV,
            'idCliente' => $request->idCliente,
            'Fecha' => $request->fecha,
            'Hora' => $request->hora,
            'idSupervisor' => $user->idsupervisor ?? $user->idPersona,
            'idstatus' => TTmpPlanificadore::ESTATUS_TEMPORAL,
            'idCreador' => $user->idPersona,
            'estatus_visita' => $request->estatus_visita ?? 0,
        ]);

        return response()->json(['success' => 'Cita agendada correctamente', 'id' => $cita->Id], 200);
    }

    /**
     * Actualizar una visita temporal.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'fecha' => 'nullable|date',
            'hora' => 'nullable',
            'idCliente' => 'nullable|string',
            'estatus_visita' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $cita = TTmpPlanificadore::withoutGlobalScopes()->find($request->id);

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        // Solo el creador puede modificar
        if ($cita->idCreador != $user->idPersona && $cita->idRFV != $user->idPersona) {
            return response()->json(['error' => 'No autorizado para modificar esta cita'], 403);
        }

        if ($request->has('fecha')) $cita->Fecha = $request->fecha;
        if ($request->has('hora')) $cita->Hora = $request->hora;
        if ($request->has('idCliente')) $cita->idCliente = $request->idCliente;
        if ($request->has('estatus_visita')) $cita->estatus_visita = $request->estatus_visita;

        $cita->save();

        return response()->json(['success' => 'Cita actualizada correctamente']);
    }

    /**
     * Eliminar una visita temporal.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $cita = TTmpPlanificadore::withoutGlobalScopes()->find($request->id);

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        if ($cita->idCreador != $user->idPersona && $cita->idRFV != $user->idPersona) {
            return response()->json(['error' => 'No autorizado para eliminar esta cita'], 403);
        }

        $cita->delete();

        return response()->json(['success' => 'Cita eliminada correctamente']);
    }

    /**
     * Muestra los clientes disponibles del RFV con sus días y horarios.
     */
    public function clientesDisponibles()
    {
        $user = Auth::user();

        $clientes = $user->Clientes()->get(['idPersona', 'nombre_completo_razon_social', 'horaMin', 'horaMax']);

        if ($clientes->isEmpty()) {
            return response()->json(['error' => 'No tiene clientes asignados'], 200);
        }

        foreach ($clientes as $cliente) {
            $dias = TPersona::find($cliente->idPersona)?->Dias()->get(['descripcion_dias_visita']);
            $cliente->diasDisponible = $dias ?? collect();
        }

        return response()->json([
            'idRFV' => $user->idPersona,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Lista los RFV disponibles según el rol del usuario.
     */
    public function rfvsDisponibles()
    {
        $user = Auth::user();

        if ($user->esRepresentante()) {
            return response()->json([
                'rfvs' => [[
                    'idPersona' => $user->idPersona,
                    'nombre' => $user->nombre_completo_razon_social,
                ]],
            ]);
        }

        $rfvs = TPersona::where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $user->idFabricante)
            ->where('idOperador', $user->idOperador)
            ->where('idestatus', 1)
            ->get(['idPersona', 'nombre_completo_razon_social'])
            ->map(fn ($r) => [
                'idPersona' => $r->idPersona,
                'nombre' => $r->nombre_completo_razon_social,
            ]);

        return response()->json(['rfvs' => $rfvs]);
    }
}
