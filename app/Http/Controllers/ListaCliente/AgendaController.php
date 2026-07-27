<?php

//app/Http/Controllers/ListaCliente/AgendaController.php
namespace App\Http\Controllers\ListaCliente;

use App\Http\Controllers\Controller;
use App\Services\AgendaService;
use App\Services\RepresentanteClienteService;
use App\Services\AccessControlService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Agenda\AgendaExport;
use Illuminate\Support\Facades\Auth;

class AgendaController extends Controller
{

    public function __construct(
        protected AgendaService $agendaService,
        protected RepresentanteClienteService $representanteService,
        protected AccessControlService $accessControl,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP', 'RFV'])) {
            return redirect()->route('dashboard.index')->with('error', 'No autorizado.');
        }

        $datosAgenda = $this->agendaService->obtenerDatosAgenda($request);

        return Inertia::render('RTR/ListaClientes', [
            'clientes' => [
                'data' => $datosAgenda['clientes']->items(),
                               'links' => $this->formatearLinks($datosAgenda['clientes']),
                               'meta' => [
                                   'current_page' => $datosAgenda['clientes']->currentPage(),
                               'last_page' => $datosAgenda['clientes']->lastPage(),
                               'per_page' => $datosAgenda['clientes']->perPage(),
                               'total' => $datosAgenda['clientes']->total(),
                               ]
            ],
            'estadisticas' => $datosAgenda['estadisticas'],
            'selectedFabricante' => $this->contextService->getActiveId(),
                               'filtros' => $request->only(['idRfv', 'idCliente', 'search', 'page']),
                               'mensaje' => $datosAgenda['mensaje'] ?? null
        ]);
    }

    public function exportarExcel(Request $request)
    {
        try {
            $user = Auth::user();
            $idFabricante = $this->contextService->getActiveId();
            $idRfv = $request->input('idRfv');

            if ($idRfv) {
                // Caso A: Se seleccionó un vendedor específico en el combo
                $datos = $this->agendaService->obtenerDatosParaExcel($request, $idRfv);
                $nombreArchivo = "agenda_vendedor_{$idRfv}_";
            } elseif ($user->idgrupo_persona === 'RFV') {
                // Caso B: El usuario es un vendedor (solo ve lo suyo)
                $datos = $this->agendaService->obtenerDatosParaExcel($request, $user->idPersona);
                $nombreArchivo = "mi_agenda_";
            } elseif ($idFabricante) {
                // Caso C: SIIF o GRT con empresa seleccionada (Descarga Global)
                $datos = $this->agendaService->obtenerDatosGeneralesEmpresa($idFabricante);
                $nombreArchivo = "agenda_general_empresa_";
            } else {
                return back()->with('error', 'Debe seleccionar una empresa o un vendedor para exportar.');
            }

            if (empty($datos)) {
                return back()->with('error', 'No se encontraron registros para exportar.');
            }

            return Excel::download(
                new AgendaExport($datos),
                                   $nombreArchivo . now()->format('Ymd_His') . '.xlsx'
            );

            return Excel::download(new AgendaExport($datos), 'agenda_' . now()->format('Ymd_His') . '.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al exportar.');
        }
    }

    // Métodos auxiliares
    public function getRepresentantes(Request $request) {
        return response()->json($this->representanteService->getRepresentantesData($request));
    }

    public function getClientes(Request $request) {
        return response()->json($this->representanteService->searchClientesData($request));
    }
    /**
     * Formatea los links de paginación para el formato esperado por el frontend.
     * Usa una ventana alrededor de la página actual (+ primeras/últimas páginas)
     * con elipsis, en lugar de listar todas las páginas de corrido.
     */
    private function formatearLinks($paginator): array
    {
        $links = [];
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $onEachSide = 2;

        // Link a la página anterior
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false
        ];

        $pageLink = fn (int $page) => [
            'url' => $paginator->url($page),
            'label' => (string) $page,
            'active' => $page === $currentPage
        ];
        $ellipsis = ['url' => null, 'label' => '...', 'active' => false];

        if ($lastPage <= ($onEachSide * 2) + 6) {
            // Pocas páginas: se muestran todas sin elipsis
            foreach (range(1, $lastPage) as $page) {
                $links[] = $pageLink($page);
            }
        } else {
            foreach (range(1, 2) as $page) {
                $links[] = $pageLink($page);
            }

            $start = max(3, $currentPage - $onEachSide);
            if ($start > 3) {
                $links[] = $ellipsis;
            }

            $end = min($lastPage - 2, $currentPage + $onEachSide);
            for ($page = $start; $page <= $end; $page++) {
                $links[] = $pageLink($page);
            }

            if ($end < $lastPage - 2) {
                $links[] = $ellipsis;
            }

            foreach (range($lastPage - 1, $lastPage) as $page) {
                $links[] = $pageLink($page);
            }
        }

        // Link a la página siguiente
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false
        ];

        return $links;
    }
}
