<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Services\CompanyContextService;
use App\Services\ProductoAdminService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductoAdminController extends Controller
{
    public function __construct(
        protected ProductoAdminService $productoService,
        protected CompanyContextService $contextService,
    ) {}

    /**
     * Lista los registros según el tipo (muestras, productos, lineas_productos, tipos_productos)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tipo = $request->route()->defaults['tipo'] ?? null;

        try {
            $paginator = $this->productoService->listarProductoPaginado($tipo, $request);

            // EVITAMOS EL ERROR DEL RESOURCE: Mapeamos la colección según el tipo de datos
            if (in_array($tipo, ['lineas_productos', 'tipos_productos'])) {
                $paginator->setCollection(
                    $paginator->getCollection()->map(function ($item) use ($tipo) {
                        if ($tipo === 'lineas_productos') {
                            return [
                                'id' => $item->id,
                                'descripcion_linea_producto' => $item->descripcion_linea_producto,
                                // 'metadata' alimenta automáticamente los inputs del ModalGen al editar
                                'metadata' => [
                                    'descripcion_linea_producto' => $item->descripcion_linea_producto,
                                ]
                            ];
                        } else {
                            return [
                                'id' => $item->idtipo_producto, // id genérico para las acciones de la tabla (row.id)
                                'idtipo_producto' => $item->idtipo_producto,
                                'descripcion_tipo_producto' => $item->descripcion_tipo_producto,
                                'metadata' => [
                                    'idtipo_producto' => $item->idtipo_producto,
                                    'descripcion_tipo_producto' => $item->descripcion_tipo_producto,
                                ]
                            ];
                        }
                    })
                );
                $itemsResponse = $paginator;
            } else {
                // Muestras y Productos siguen usando el Resource original
                $itemsResponse = ProductoResource::collection($paginator);
            }

            return Inertia::render('Administrar/seccionGen', [
                'tipo'        => $tipo,
                'permissions' => [
                    'can_create' => $this->checkProductPermission($user, 'create'),
                    'can_update' => $this->checkProductPermission($user, 'edit'),
                    'can_delete' => $this->checkProductPermission($user, 'delete'),
                ],
                'items'   => $itemsResponse,
                'options' => $this->getOptions($tipo),
                'filters' => $request->only(['search', 'size'])
            ]);
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@index error', ['exception' => $e->getMessage(), 'tipo' => $tipo]);
            return redirect()->route('dashboard.index')->with('error', 'Ocurrió un error al listar los registros');
        }
    }

    /**
     * Crea un nuevo registro
     */
    public function store(Request $request)
    {
        $tipo = $request->route()->defaults['tipo'];

        if (!$this->checkProductPermission(Auth::user(), 'create')) {
            abort(403, 'No tiene permisos para crear este registro.');
        }

        // REGLAS DINÁMICAS: Evitamos exigir datos de productos a líneas o tipos
        $reglas = match ($tipo) {
            'lineas_productos' => [
                'id' => 'required|max:5',
                'descripcion_linea_producto' => 'required|string|max:255',
            ],
            'tipos_productos' => [
                'idtipo_producto' => 'required|string|max:5',
                'descripcion_tipo_producto' => 'required|string|max:255',
            ],
            'muestras', 'productos' => [
                'id' => 'required|max:5',
                'producto' => 'required|string',
                'existencia' => 'required|numeric|min:0',
                'linea' => 'required',
                'tipo_producto' => 'required',
                'mayorista' => 'required',
                'fecha_vencimiento' => 'required|date',
                'fecha_registro' => 'required|date',
                'presentacion' => 'required|string',
            ],
            default => null
        };

        if (!$reglas) {
            return back()->withErrors(['tipo' => 'Tipo no válido']);
        }

        // Reglas específicas adicionales para productos tradicionales
        if ($tipo === 'muestras') {
            $reglas['lote'] = 'required';
        } elseif ($tipo === 'productos') {
            $reglas['precio'] = 'required|numeric|min:0';
            $reglas['descuento'] = 'required|numeric|min:0|max:100';
        }

        $validated = $request->validate($reglas);

        try {
            $this->productoService->crearProducto($tipo, $validated);
            return back()->with('success', 'Registro creado con éxito');
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@store error', [
                'exception' => $e->getMessage(), 
                'tipo' => $tipo, 
                'input' => $request->except(['password', 'password_confirmation'])
            ]);
            return back()->with('error', 'Ocurrió un error al crear el registro');
        }
    }

    /**
     * Actualiza un registro existente
     */
    public function update(Request $request, $id)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;

        if (!$this->checkProductPermission(Auth::user(), 'edit')) {
            abort(403, 'No tiene permisos para editar este registro.');
        }

        try {
            $this->productoService->actualizarProducto($tipo, $id, $request->all());
            return back()->with('success', 'Registro actualizado');
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@update error', ['exception' => $e->getMessage(), 'id' => $id, 'tipo' => $tipo]);
            return back()->with('error', 'Ocurrió un error al actualizar el registro');
        }
    }

    /**
     * Eliminación lógica (idestatus = 0)
     */
    public function destroy(Request $request, $id)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;

        if (!$this->checkProductPermission(Auth::user(), 'delete')) {
            abort(403, 'No tiene permisos para eliminar este registro.');
        }

        try {
            $this->productoService->eliminarProducto($tipo, $id);
            return back()->with('success', 'Registro eliminado');
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@destroy error', ['exception' => $e->getMessage(), 'id' => $id, 'tipo' => $tipo]);
            return back()->with('error', 'Ocurrió un error al eliminar el registro');
        }
    }

    private function checkProductPermission($user, $action)
    {
        if ($user->idgrupo_persona === 'RFV') {
            return false;
        }
        return true;
    }

    private function getOptions()
    {
        return [   
            'linea' => [],
            'tipo_producto' => [],
            'mayorista' => [],
        ];
    }
}