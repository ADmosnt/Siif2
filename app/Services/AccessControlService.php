<?php
//App\Services\AccessControlService.php 
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AccessControlService
{
    /**
     * Verifica si el usuario actual pertenece a uno de los roles permitidos.
     */
    public function hasAnyRole(array $roles): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return in_array($user->idgrupo_persona, $roles);
    }

    /**
     * Lógica centralizada para denegar acceso con log.
     */
    public function logUnauthorizedAccess(string $module): void
    {
        $user = Auth::user();
        Log::warning("Intento de acceso no autorizado", [
            'modulo' => $module,
            'user_id' => $user->idPersona ?? 'Anónimo',
            'rol' => $user->idgrupo_persona ?? 'N/A',
            'ip' => request()->ip()
        ]);
    }
}