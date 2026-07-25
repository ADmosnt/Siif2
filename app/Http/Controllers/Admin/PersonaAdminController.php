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
use App\Services\CompanyContextService;
use App\Services\RepresentanteClienteService;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PersonaAdminController extends Controller
{
    public function __construct(
        protected PersonaAdminService $personaService,
        protected CompanyContextService $contextService,
        protected RepresentanteClienteService $representanteClienteService,
        protected AccessControlService $accessControl,
    ) {}

    /**
     * Lista los registros según el tipo (clientes, representantes, etc.)
     */
    public function index(Request $request)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;
        $user = Auth::user();

        if ($tipo === 'clientes' && $user->idgrupo_persona === 'RFV'){
            $paginator = $this->representanteClienteService->getClientesData($request, $user->idPersona);
        }

        else {
            $paginator = $this->personaService->listarPaginado($tipo, $request);
        }
        try {
            return Inertia::render('Administrar/seccionGen', [
                'tipo'    => $tipo,
                'permissions' => [
                    'can_create' => $this->checkGlobalPermission($tipo, 'create'),
                    'can_update' => $this->checkGlobalPermission($tipo, 'edit'),
                    'can_delete' => $this->checkGlobalPermission($tipo, 'delete'),
                ],
                'items'   => PersonaResource::collection($paginator),
                'options' => $this->getOptions($tipo),
                'filters' => $request->only(['search', 'size'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PersonaAdminController@index error', ['exception' => $e->getMessage(), 'tipo' => $tipo]);
            return redirect()->route('dashboard.index')->with('error', 'Ocurrió un error al listar los registros');
        }
    }

    private function checkGlobalPermission($tipo, $action)
    {
        $user = Auth::user();

        if ($tipo === 'empresas') {
        return $user->idgrupo_persona === 'SIIF';
        }

        if ($user->idgrupo_persona === 'RFV') {
            return ($tipo === 'clientes');
        }

        return true;
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
            // 2. Aplicamos la lógica de Vendedores (RFV)
            $user = Auth::user();
            if ($user->idgrupo_persona === 'RFV') {
                // Si el usuario es RFV, solo se ve a sí mismo
                $options['vendedor'] = [[
                    'label' => $user->name, 
                    'value' => $user->idPersona
                ]];
            } else {
                // Si es un rol superior, cargamos todos los RFV del fabricante
                $options['vendedor'] = \App\Models\TPersona::where('idgrupo_persona', 'RFV')
                    ->where('idfabricante', $idFabricante) // Asegúrate de que $idFabricante esté definido
                    ->where('idestatus', 1)
                    ->get()
                    ->map(fn($v) => [
                        'label' => $v->nombre_completo,
                        'value' => $v->idPersona
                    ])->toArray();
            }

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
    $user = Auth::user();

    if (!$this->checkGlobalPermission($tipo, 'create')) {
        abort(403, 'No tiene permisos para crear este tipo de registro.');
    }

    // Reglas base comunes para todos
    $reglas = [
        'nombre'    => 'required|string|max:100',
        
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
                'vendedor'     => 'required',
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

        case 'empresas':
            $reglas['idOperador']   = 'required|string';
            $reglas['idFabricante'] = 'required|string';
            break;
    }        

    /** @var array $validated */
    $validated = $request->validate($reglas);

    // Un RFV solo puede crear clientes asignados a si mismo, sin importar
    // que "vendedor" haya mandado el formulario (defensa en profundidad,
    // el select de vendedor ya solo le muestra su propio nombre).
    if ($tipo === 'clientes' && $user->idgrupo_persona === 'RFV') {
        $validated['vendedor'] = $user->idPersona;
    }

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
        $user = Auth::user();

        if (!$this->checkGlobalPermission($tipo, 'edit')) {
            abort(403, 'No tiene permisos para editar este registro.');
        }

        if ($tipo === 'clientes' && !$this->accessControl->canAccessCliente((string) $id, $user)) {
            abort(403, 'No tiene permisos para editar este cliente.');
        }

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
        $tipo = $request->route()->defaults['tipo'] ?? null;
        $user = Auth::user();

        if (!$this->checkGlobalPermission($tipo, 'delete')) {
            abort(403, 'No tiene permisos para eliminar este registro.');
        }

        if ($tipo === 'clientes' && !$this->accessControl->canAccessCliente((string) $id, $user)) {
            abort(403, 'No tiene permisos para eliminar este cliente.');
        }

        try {
            $this->personaService->eliminarPersona($id);
            return back()->with('success', 'Registro eliminado');
        } catch (\Throwable $e) {
            Log::error('PersonaAdminController@destroy error', ['exception' => $e->getMessage(), 'id' => $id]);
            return back()->with('error', 'Ocurrió un error al eliminar el registro');
        }
    }

        public function toggleFabricanteStatus(Request $request, string $idFabricante)
    {
        if (!$this->accessControl->hasAnyRole(['SIIF'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $result = $this->personaService->toggleFabricanteStatus($idFabricante);
            $statusText = $result['new_status'] == 1 ? 'activada' : 'desactivada';
            return response()->json([
                'message' => "Empresa {$statusText} correctamente. {$result['affected']} usuario(s) afectado(s).",
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('toggleFabricanteStatus error', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al cambiar el estatus'], 500);
        }
    }
}