<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  <-- Los tres puntos capturan todo como un array
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $accessControl = app(\App\Services\AccessControlService::class);

        // Ahora $roles SIEMPRE es un array, incluso si solo pasas uno
        if (!$accessControl->hasAnyRole($roles)) {
            
            // Para el log, podemos unir los roles en un string
            $moduleName = $request->route()->getName() ?? $request->path();
            $accessControl->logUnauthorizedAccess($moduleName);
            
            return redirect()->route('dashboard.index')
                ->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
