<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Services\CompanyContextService;
use App\Services\ProductoAdminService;
use App\Services\AccessControlService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class ProductoAdminController extends Controller
{
    public function __construct(
        protected ProductoAdminService $productoService,
        protected CompanyContextService $contextService,
        protected AccessControlService $accessControl
    ) {}

        /**
     * Lista los registros según el tipo (muestras o productos-lista)
     */
    public function index(Request $request)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;

        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP'])) {
            $this->accessControl->logUnauthorizedAccess('Conciliación de Facturas');
            
            return redirect()->route('dashboard.index')->with('error', 'No tienes permisos para acceder a este módulo.');
        }
        try {
            $paginator = $this->productoService->listarProductoPaginado($tipo, $request);

            return Inertia::render('Administrar/seccionGen', [
                'tipo'    => $tipo,
                'items'   => ProductoResource::collection($paginator),
                'options' => $this->getOptions($tipo),
                'filters' => $request->only(['search'])
            ]);
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@index error', ['exception' => $e->getMessage(), 'tipo' => $tipo]);
            return redirect()->route('dashboard.index')->with('error', 'Ocurrió un error al listar los registros');
        }
    }

    private function getOptions()
    {
        $options = 
        [   
            'linea' => [],
            'tipo_producto' => [],
            'mayorista' => [],
        ];

        return $options;
    }
        /**
     * Crea un nuevo registro
     */
    public function store(Request $request)
    {
    $tipo = $request->route()->defaults['tipo'];

    //reglas de validación

    $reglas = [
        'id' => 'required|max:9',
        'producto' => 'required|string',
        'existencia' => 'required|numeric|min:0',
        'linea' => 'required',
        'tipo_producto' => 'required',
        'mayorista' => 'required',
        'fecha_vencimiento' => 'required|date',
        'fecha_registro' => 'required|date',
        'presentacion' => 'required|string',
    ];

    switch ($tipo) {
        case 'muestras':
            $reglas['lote'] = 'required';
            break;

        case 'productos':
            $reglas = array_merge($reglas, [
                'precio' => 'required|numeric|min:0',
                'descuento' => 'required|numeric|min:0|max:100',
            ]);
            break;

        default:
            return back()->withErrors(['tipo' => 'Tipo no válido']);
    }
        $validated = $request->validate($reglas);

        try {
            $this->productoService->crearProducto($tipo, $validated);
            return back()->with('success', 'Registro creado con éxito');
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@store error', ['exception' => $e->getMessage(), 'tipo' => $tipo, 'input' => $request->except(['password', 'password_confirmation'])]);
            return back()->with('error', 'Ocurrió un error al crear el registro');
        }
    }
        /**
     * Actualiza un registro existente
     */

    public function update(Request $request, $id)
    {
        $tipo = $request->route()->defaults['tipo'] ?? null;

        try {
            $this->productoService->actualizarProducto($id, $request->all());
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
        try {
            $this->productoService->eliminarProducto($id);
            return back()->with('success', 'Registro eliminado');
        } catch (\Throwable $e) {
            Log::error('ProductoAdminController@destroy error', ['exception' => $e->getMessage(), 'id' => $id]);
            return back()->with('error', 'Ocurrió un error al eliminar el registro');
        }
    }
}

