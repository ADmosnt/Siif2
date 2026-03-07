<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Registra la suscripción push del navegador del usuario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $user = Auth::user();

        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
            $request->input('content_encoding', 'aesgcm')
        );

        return response()->json(['success' => 'Suscripción registrada']);
    }

    /**
     * Elimina la suscripción push del usuario.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $user = Auth::user();
        $user->deletePushSubscription($request->input('endpoint'));

        return response()->json(['success' => 'Suscripción eliminada']);
    }
}
