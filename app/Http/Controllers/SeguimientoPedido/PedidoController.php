<?php

namespace App\Http\Controllers\SeguimientoPedido;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetPedidosRequest;
use App\Services\PedidoService;
use App\Services\AccessControlService;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    public function __construct(
        protected PedidoService $pedidoService,
        protected AccessControlService $accessControl
    ) {}

    /**
     * Vista principal (Inertia)
     */
    public function seguimiento(Request $request): Response
    {
        return Inertia::render('SeguimientoPedidos', [
            'user_role' => $request->user()->idgrupo_persona
        ]);
    }

    /**
     * API: Listado de órdenes filtradas
     */
    public function getOrdenesFiltradas(GetPedidosRequest $request): JsonResponse
    {
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP', 'RFV'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $ordenes = $this->pedidoService->getOrdenesFiltradas(
            $request->estatus_id,
            $request->fecha_inicio,
            $request->fecha_fin,
            $request->rfv_id,
            $request->input('per_page', 15),
            $request->input('page', 1)
        );

        return response()->json([
            'data' => $ordenes->items(),
            'meta' => [
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'total' => $ordenes->total(),
            ],
            'links' => [
                'next' => $ordenes->nextPageUrl(),
                'prev' => $ordenes->previousPageUrl(),
            ]
        ]);
    }

    /**
     * API: Detalle de una orden
     */
    public function getOrdenDetalle(string $id): JsonResponse
    {
        $ordenDetalle = $this->pedidoService->getOrdenDetalle((int)$id);

        if (!$ordenDetalle) {
            return response()->json(['error' => 'Orden no encontrada o sin acceso'], 404);
        }

        return response()->json(['data' => $ordenDetalle]);
    }

    public function getEstatus(): JsonResponse
    {
        return response()->json($this->pedidoService->getEstatusDisponibles());
    }

    public function getRepresentantes(Request $request, RepresentanteClienteService $repService): JsonResponse
    {
        return response()->json($repService->getRepresentantesData($request));
    }
}