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
     * Formatea los links de paginación para el formato esperado por el frontend
     */
    private function formatearLinks($paginator): array
    {
        $links = [];
        
        // Link a la primera página
        $links[] = [
            'url' => $paginator->url(1),
            'label' => '&laquo; Previous',
            'active' => false
        ];

        // Links de las páginas
        foreach (range(1, $paginator->lastPage()) as $page) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $paginator->currentPage()
            ];
        }

        // Link a la última página
        $links[] = [
            'url' => $paginator->url($paginator->lastPage()),
            'label' => 'Next &raquo;',
            'active' => false
        ];

        return $links;
    }
}