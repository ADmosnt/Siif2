<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificacionResource;
use App\Models\TNotificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NotificacionController extends Controller
{
    /**
     * Lista las notificaciones del usuario.
     */
    public function index()
    {
        $user = Auth::user();

        $notificaciones = TNotificacion::where(function ($q) use ($user) {
                $q->where('idPersona', $user->idPersona)
                  ->orWhere('idFabricante', $user->idFabricante);
            })
            ->where('idestatus', 1)
            ->orderBy('fecha_registro', 'desc')
            ->take(20)
            ->get();

        return NotificacionResource::collection($notificaciones);
    }

    /**
     * Crea una nueva notificación.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:500',
            'idtipo' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        TNotificacion::create([
            'idOperador' => $user->idOperador,
            'idFabricante' => $user->idFabricante,
            'idPersona' => $user->idPersona,
            'idtipo' => $request->idtipo ?? 1,
            'descripcion_notoficacion' => $request->descripcion,
            'fecha_registro' => Carbon::now(),
            'idestatus' => 1,
        ]);

        return response()->json(['success' => 'Notificación creada']);
    }

    /**
     * Actualiza una notificación.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'descripcion' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $notificacion = TNotificacion::find($request->id);
        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->descripcion_notoficacion = $request->descripcion;
        $notificacion->save();

        return response()->json(['success' => 'Notificación actualizada']);
    }

    /**
     * Elimina (soft) una notificación.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $notificacion = TNotificacion::find($request->id);
        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->idestatus = 0;
        $notificacion->save();

        return response()->json(['success' => 'Notificación eliminada']);
    }

    /**
     * Marca una notificación como procesada/vista.
     */
    public function marcarVista(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $notificacion = TNotificacion::find($request->id);
        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->idestatus = 2;
        $notificacion->save();

        return response()->json(['success' => 'Notificación marcada como vista']);
    }
}
