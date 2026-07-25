<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        // Comparacion estricta: con == 0, un idestatus NULL (columna nullable)
        // tambien evalua como igual a 0 en PHP y desloguea al usuario en
        // silencio en cualquier request, aunque su cuenta nunca haya sido
        // desactivada explicitamente.
        $status = Auth::user()?->idestatus;

        if (Auth::check() && $status !== null && (int) $status === 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'name' => 'Su cuenta ha sido desactivada. Contacte al administrador.',
            ]);
        }

        return $next($request);
    }
}