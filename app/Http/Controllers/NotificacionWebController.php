<?php

namespace App\Http\Controllers;

use App\Models\TNotificacion;
use App\Models\TPersona;
use App\Models\TTipoNotificacion;
use App\Services\CompanyContextService;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificacionWebController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService,
        protected RepresentanteClienteService $representanteService
    ) {}

    /**
     * Muestra la vista de notificaciones con los datos necesarios.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        // Empresas (solo SIIF puede ver todas)
        $empresasQuery = TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'FABR');

        if (in_array($user->idgrupo_persona, ['GRT', 'RFV', 'SUP'])) {
            $empresasQuery->where('idFabricante', $user->idFabricante);
        }

        $empresas = $empresasQuery->get(['idPersona', 'nombre_completo_razon_social', 'idFabricante']);

        // Representantes (RFVs) del contexto activo
        $representantes = $this->representanteService->getRepresentantesData($request);

        // Tipos de notificación
        $tiposQuery = TTipoNotificacion::where('idestatus', 1);
        if ($activeFabricante) {
            $tiposQuery->where('idOperador', $this->contextService->getActiveOperador());
        }
        $tipos = $tiposQuery->get(['id', 'titulo']);

        // Bandeja de notificaciones según rol
        $notificacionesQuery = TNotificacion::with('tipo')
            ->where('idestatus', '!=', 0)
            ->orderBy('fecha_registro', 'desc');

        if ($user->idgrupo_persona === 'RFV') {
            // RFV solo ve las que le enviaron a él o las de su fabricante (sin destino específico)
            $notificacionesQuery->where(function ($q) use ($user) {
                $q->where('idPersona', $user->idPersona)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('idFabricante', $user->idFabricante)
                         ->where('idPersona', '!=', $user->idPersona);
                  });
            });
        } elseif (in_array($user->idgrupo_persona, ['GRT', 'SUP'])) {
            // GRT/SUP ven las de su fabricante
            $notificacionesQuery->where('idFabricante', $user->idFabricante);
        } elseif ($user->idgrupo_persona === 'SIIF') {
            // SIIF: si tiene empresa seleccionada, filtra; si no, ve todas
            if ($activeFabricante) {
                $notificacionesQuery->where('idFabricante', $activeFabricante);
            }
        }

        $notificaciones = $notificacionesQuery->take(50)->get()->map(function ($n) {
            return [
                'idNotificacion' => $n->idNotificacion,
                'descripcion' => $n->descripcion_notoficacion,
                'tipo' => $n->tipo?->titulo,
                'fecha' => $n->fecha_registro,
                'idestatus' => $n->idestatus,
                'idPersona' => $n->idPersona,
            ];
        });

        return Inertia::render('Notificacion', [
            'empresas' => $empresas,
            'representantes' => $representantes,
            'tipos' => $tipos,
            'notificaciones' => $notificaciones,
            'activeCompanyId' => $activeFabricante,
            'usuario' => [
                'idgrupo_persona' => $user->idgrupo_persona,
                'nombre' => $user->nombre_completo_razon_social,
            ],
        ]);
    }
}
