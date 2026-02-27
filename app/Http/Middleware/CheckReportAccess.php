<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckReportAccess
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            // Para Inertia: redirigir, no JSON
            return redirect()->route('login');
        }
        
        Log::info('Acceso a reportes autorizado', [
            'user_id' => $user->idPersona,
            'user_group' => $user->idgrupo_persona,
            'user_fabricante' => $user->idFabricante
        ]);
        
        return $next($request);
    }
}