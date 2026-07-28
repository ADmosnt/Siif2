<?php

namespace App\Http\Controllers;

use App\Models\TNotificacion;
use App\Models\TPersona;
use App\Models\TTipoNotificacion;
use App\Notifications\SiifPushNotification;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class NotificacionPushController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}

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

        // idFabricante/idOperador deben ser los de la EMPRESA ACTIVA en el
        // contexto (el selector de arriba en la pantalla), no los del
        // usuario que envia: para SIIF (sin empresa fija) usar
        // $user->idFabricante hacia que la busqueda de destinatarios
        // filtrara por una empresa que no era la seleccionada, dando
        // "enviada a 0 usuarios" siempre en el envio general. Para
        // GRT/SUP getActiveId()/getActiveOperador() ya devuelven lo mismo
        // que $user->idFabricante/idOperador, asi que no cambia nada para
        // ellos.
        $activeFabricante = $this->contextService->getActiveId();
        $activeOperador = $this->contextService->getActiveOperador();

        if (!$activeFabricante) {
            return back()->with('error', 'Seleccione una empresa antes de enviar la notificación.');
        }

        // Guardar en t_notificaciones. idPersona: si es para un RFV
        // especifico lleva su id; si es general (sin RFV seleccionado) debe
        // quedar en blanco (asi lo documenta la propia columna en la BD) -
        // no el id de quien la envia, que es lo que hacia esto antes y
        // mostraba "Para: SIIFADMIN" en vez de indicar que fue para toda
        // la empresa.
        $notificacion = TNotificacion::create([
            'idOperador' => $activeOperador,
            'idFabricante' => $activeFabricante,
            'idPersona' => $request->idPersona_destino ?? '',
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
            // Enviar a todos los RFV de la empresa activa
            $destinatarios = TPersona::where('idFabricante', $activeFabricante)
                ->where('idgrupo_persona', TPersona::TIPO_REPRESENTANTE)
                ->where('idestatus', 1)
                ->get();
        }

        // Enviar push notification a cada destinatario que tenga suscripción
        $enviados = 0;
        foreach ($destinatarios as $destinatario) {
            if ($destinatario->pushSubscriptions()->exists()) {
                $destinatario->notify(new SiifPushNotification(
                    $titulo,
                    $request->descripcion,
                    $notificacion->idNotificacion
                ));
                $enviados++;
            }
        }

        Log::info('Notificación push enviada', [
            'emisor' => $user->name,
            'destinatarios_push' => $enviados,
            'total_destinatarios' => $destinatarios->count(),
        ]);

        return back()->with('success', "Notificación enviada a {$destinatarios->count()} usuario(s). Push enviado a {$enviados}.");
    }
}