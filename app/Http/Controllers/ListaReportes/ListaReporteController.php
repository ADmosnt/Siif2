<?php

namespace App\Http\Controllers\ListaReportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetListaReporteRequet;
use App\Services\ListaReporteService;
use App\Services\AccessControlService;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListaReporteController extends Controller
{
    public function __construct(
        protected ListaReporteService $reportService,
        protected AccessControlService $accessControl
    ) {}

    /**
     * Obtiene las actividades de los RFV permitidos.
     */
    public function getReports(GetListaReporteRequet $request): JsonResponse
    {
        // 1. Validar acceso: Solo roles autorizados
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP', 'RFV'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $perpage = $request->input('size', 15);
        $page = $request->input('page', 1);

        $activities = $this->reportService->getActivitiesByDateRange(
            $request->fechaInicio,
            $request->fechaFin,
            $perpage,
            $page,
            $request->input('idRfv')
        );

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ],
            'links' => [
                'first' => $activities->url(1),
                'last' => $activities->url($activities->lastPage()),
                'prev' => $activities->previousPageUrl(),
                'next' => $activities->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Este método se mantiene para alimentar los select de la UI.
     * Al usar el repService, ya viene filtrado por el activeFabricante automáticamente.
     */
    public function getRepresentantes(Request $request, RepresentanteClienteService $repService): JsonResponse
    {
        $data = $repService->getRepresentantesData($request);
        return response()->json($data);
    }
}