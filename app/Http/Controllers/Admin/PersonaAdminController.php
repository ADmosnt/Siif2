<?php
// Controlador genérico para administrar personas (clientes, representantes, etc.)
// app/Http/Controllers/Admin/PersonaAdminController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PersonaAdminService;
use App\Http\Resources\PersonaResource;
use App\Models\TCiclo;
use App\Models\TTipoPersona;
use App\Models\TEspecialidade;
use App\Models\TClasePersona;
use App\Models\TRankingCliente;
use App\Models\TFrecuenciaVisita;
use App\Services\AccessControlService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PersonaAdminController extends Controller
{
    public function __construct(
        protected PersonaAdminService $personaService,
        protected CompanyContextService $contextService,
        protected AccessControlService $accessControl
    ) {}

    /**
     * Lista los registros según el tipo (clientes, representantes, etc.)
     */
    public function index(Request $request)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP'])) {
            $this->accessControl->logUnauthorizedAccess('Conciliación de Facturas');
            return redirect()->route('dashboard.index')->with('error', 'No tienes permisos para acceder a este módulo.');
        }

        try {
            $paginator = $this->personaService->listarPaginado($tipo, $request);

            return Inertia::render('Administrar/seccionGen', [
                'tipo'    => $tipo,
                'items'   => PersonaResource::collection($paginator),
                'options' => $this->getOptions($tipo),
                'filters' => $request->only(['search'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PersonaAdminController@index error', ['exception' => $e->getMessage(), 'tipo' => $tipo]);
            return redirect()->route('dashboard.index')->with('error', 'Ocurrió un error al listar los registros');
        }
    }

    private function getOptions($tipo)
    {
    $idFabricante = $this->contextService->getActiveId();
    $options = [
        // Solo opciones estáticas pequeñas
        'tipo_doc' => TTipoPersona::all()->map(fn($t) => [
            'label' => $t->descripcion_tipo_persona,
            'value' => $t->id
        ])->toArray(),
        
        'pais' => [],
        'estado' => [],
        'ciudad' => [],
        'supervisor' => [],
    ];

    switch ($tipo) {
        case 'clientes':
        $options['especialidad'] = TEspecialidade::where('estatus',1)
            ->get()
            ->map(fn($e) => [
                'label' => $e->descripcion_especialidad,
                'value' => $e->id
            ])->toArray();

        $options['clase'] = TClasePersona::where('estatus',1)
            ->get()
            ->map(fn($c) => [
                'label' => $c->descripcion,
                'value' => $c->id
        ])->toArray();

        $options['ranking'] = TRankingCliente::withoutglobalScopes()
            ->where('idFabricante', $idFabricante)
            ->where('estatus', 1)
            ->get()
            ->map(fn($r) => [
                'label' => $r->descripcion_ranking_cliente,
                'value' => $r->id
        ])->toArray();

        $options['frecuencia'] = TFrecuenciaVisita::withoutglobalScopes()
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->get()
            ->map(fn($f) => [
                'label' => $f->descripcion_frecuencia_visitas,
                'value' => $f->idfrecuencia
    ])->toArray();
        break;

      case 'representantes':
            $options['ciclo'] = TCiclo::withoutglobalScopes()
                ->where('idFabricante', $idFabricante)
                ->where('idestatus', 1)
                ->get()
                ->map(fn($p) => [
                    'label' => $p->descripcion_ciclos,
                    'value' => $p->id
                ])->toArray();
            break;
    }
    return $options;
}
    /**
     * Crea un nuevo registro
     */
    public function store(Request $request)
    {
    $tipo = $request->route('tipo');

    // Reglas base comunes para todos
    $reglas = [
        'nombre'    => 'required|string|max:100',
        'apellido'  => 'required|string|max:100',
        'tipo_doc'  => 'required',
        'documento' => 'required|string',
        'email'     => 'required|email',
        'telefono'  => 'required',
        'direccion' => 'required',
        'pais'      => 'required',
        'estado'    => 'required',
        'ciudad'    => 'required',
    ];

    // Reglas específicas por tipo
    switch ($tipo) {
        case 'clientes':
            $reglas = array_merge($reglas, [
                'especialidad' => 'required',
                'clase'        => 'required',
                'ranking'      => 'required',
                'frecuencia'   => 'required',
            ]);
            break;

        case 'mayoristas':
            $reglas['descuento'] = 'required|numeric|min:0';
            break;

        case 'gerentes':
            $reglas['username']   = 'required|unique:t_personas,name';
            $reglas['password']   = 'required|min:6';
            $reglas['supervisor'] = 'required';
            break;

        case 'representantes':
            $reglas['username']   = 'required|unique:t_personas,name';
            $reglas['password']   = 'required|min:6';
            $reglas['supervisor'] = 'required';
            $reglas['ciclo']      = 'required';
            break;

        case 'supervisores':
            $reglas['username']   = 'required|unique:t_personas,name';
            $reglas['password']   = 'required|min:6';
            break;
    }

    /** @var array $validated */
    $validated = $request->validate($reglas);

    try {
        $this->personaService->crearPersona($tipo, $validated);
        return redirect()->back()->with('success', 'Registro creado con éxito');
    } catch (\Throwable $e) {
        Log::error('PersonaAdminController@store error', ['exception' => $e->getMessage(), 'tipo' => $tipo, 'input' => $request->except(['password', 'password_confirmation'])]);
        return redirect()->back()->with('error', 'Ocurrió un error al crear el registro');
    }
}

    /**
     * Actualiza un registro existente
     */
    public function update(Request $request, $id)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;

        try {
            $this->personaService->actualizarPersona($id, $request->all());
            return back()->with('success', 'Registro actualizado');
        } catch (\Throwable $e) {
            Log::error('PersonaAdminController@update error', ['exception' => $e->getMessage(), 'id' => $id, 'tipo' => $tipo]);
            return back()->with('error', 'Ocurrió un error al actualizar el registro');
        }
    }

    /**
     * Eliminación lógica (idestatus = 0)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->personaService->eliminarPersona($id);
            return back()->with('success', 'Registro eliminado');
        } catch (\Throwable $e) {
            Log::error('PersonaAdminController@destroy error', ['exception' => $e->getMessage(), 'id' => $id]);
            return back()->with('error', 'Ocurrió un error al eliminar el registro');
        }
    }
}