<?php

namespace App\Http\Controllers;

use App\Models\TNotificacion;
use App\Models\TPersona;
use App\Models\TTipoNotificacion;
use App\Notifications\SiifPushNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use NotificationChannels\WebPush\Events\NotificationSent as WebPushNotificationSent;
use NotificationChannels\WebPush\Events\NotificationFailed as WebPushNotificationFailed;

class NotificacionPushController extends Controller
{
    /**
     * Envía una notificación (la guarda en BD y dispara push a los destinatarios).
     * Solo SIIF, GRT y SUP pueden usar este endpoint.
     */
    public function enviar(Request $request)
    {
        $user = Auth::user();

        // Verificar que el usuario es SIIF, GRT o SUP
        $gruposPermitidos = [
            TPersona::TIPO_ADMINISTRADOR,
            TPersona::TIPO_GERENTE,
            TPersona::TIPO_SUPERVISOR,
        ];

        if (!in_array($user->idgrupo_persona, $gruposPermitidos)) {
            return back()->with('error', 'No tiene permisos para enviar notificaciones.');
        }

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:500',
            'idtipo' => 'nullable|integer',
            'idPersona_destino' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Guardar en t_notificaciones
        $notificacion = TNotificacion::create([
            'idOperador' => $user->idOperador,
            'idFabricante' => $user->idFabricante,
            'idPersona' => $request->idPersona_destino ?? $user->idPersona,
            'idtipo' => $request->idtipo ?? 1,
            'descripcion_notoficacion' => $request->descripcion,
            'fecha_registro' => Carbon::now(),
            'idestatus' => 1,
        ]);

        // Determinar título
        $tipoNotificacion = $request->idtipo
            ? TTipoNotificacion::find($request->idtipo)
            : null;
        $titulo = $tipoNotificacion?->titulo ?? 'SIIF2 - Notificación';

        // Determinar destinatarios push
        if ($request->idPersona_destino) {
            // Enviar a un usuario específico
            $destinatarios = TPersona::where('idPersona', $request->idPersona_destino)
                ->where('idestatus', 1)
                ->get();
        } else {
            // Enviar a todos los RFV del mismo fabricante
            $destinatarios = TPersona::where('idFabricante', $user->idFabricante)
                ->where('idgrupo_persona', TPersona::TIPO_REPRESENTANTE)
                ->where('idestatus', 1)
                ->get();
        }

        // Enviar push notification a cada destinatario que tenga suscripción.
        // $enviados/$fallidos se basan en el resultado REAL de la entrega
        // (eventos que dispara el paquete de webpush), no solo en si existia
        // una suscripcion - antes se contaba como "enviado" con solo intentar,
        // sin saber si el push realmente llego.
        $conSuscripcion = 0;
        $enviados = 0;
        $fallidos = 0;

        Event::listen(WebPushNotificationSent::class, function () use (&$enviados) {
            $enviados++;
        });
        Event::listen(WebPushNotificationFailed::class, function () use (&$fallidos) {
            $fallidos++;
        });

        foreach ($destinatarios as $destinatario) {
            if ($destinatario->pushSubscriptions()->exists()) {
                $conSuscripcion++;
                $destinatario->notify(new SiifPushNotification(
                    $titulo,
                    $request->descripcion,
                    $notificacion->idNotificacion
                ));
            }
        }

        Log::info('Notificación push enviada', [
            'emisor' => $user->name,
            'total_destinatarios' => $destinatarios->count(),
            'con_suscripcion' => $conSuscripcion,
            'push_entregados' => $enviados,
            'push_fallidos' => $fallidos,
        ]);

        return back()->with('success', "Notificación enviada a {$destinatarios->count()} usuario(s). Push entregado a {$enviados} de {$conSuscripcion} suscripciones."
            . ($fallidos > 0 ? " ({$fallidos} fallaron, ver logs)" : ''));
    }
}