<?php

namespace App\Http\Controllers\Ordenes;
use App\Http\Controllers\Controller;
use App\Models\TOrdene;
use App\Models\TEstatusOrdene;
use App\Models\TPersona;
use App\Models\TProducto;
use App\Services\ReporteDataService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProcesarOrdenController extends Controller
{
    protected ReporteDataService $reporteDataService;

    public function __construct(ReporteDataService $reporteDataService)
    {
        $this->reporteDataService = $reporteDataService;
    }

    /**
     * Crea la orden y sus relaciones.
     */
    public function store(Request $request)
    {
        // 1. Validación de DATOS
        $validator = Validator::make($request->all(), [
            'cliente' => 'required|string|exists:t_personas,idPersona',
            'representante' => 'required|string|exists:t_personas,idPersona',
            'mayoristas' => 'required|array|min:1',
            'mayoristas.*.id' => 'required|string|exists:t_personas,idPersona',
            'mayoristas.*.nombre' => 'required|string',
            'descripcion' => 'nullable|string|max:256',
            'impuesto' => 'required|numeric|min:0|max:100',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|string|exists:t_productos,idproducto',
            'productos.*.unidades' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
        ], [
            'cliente.exists' => 'El cliente seleccionado no es válido.',
            'representante.exists' => 'El representante seleccionado no es válido.',
            'mayoristas.*.id.exists' => 'Uno de los mayoristas no es válido.',
            'productos.*.id.exists' => 'Uno de los productos no es válido.',
            'productos.*.unidades.min' => 'Las unidades deben ser al menos 1.',
        ]);

        if ($validator->fails()) {
            Log::warning('Validación fallida al procesar pedido', $validator->errors()->toArray());
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 2. Determinar fabricante
        $idFabricante = $this->reporteDataService->obtenerIdFabricante($request->representante);
        if (!$idFabricante) {
            Log::error('Fabricante no encontrado para el RFV', ['rfv' => $request->representante]);
            return redirect()->back()->with('error', 'No se pudo determinar el fabricante.');
        }

        // 3. Obtener estado "Enviada" para este fabricante
        $enviada = $this->reporteDataService->obtenerEstadoPorFabricante('Enviada', $idFabricante);
        
        // Si no existe, crearlo (mejor que fallar)
        if (!$enviada) {
            Log::warning('Estado "Enviada" no encontrado para fabricante. Creando estado predeterminado.', ['idFabricante' => $idFabricante]);
            $enviada = TEstatusOrdene::create([
                'idOperador' => Auth::user()->idOperador,
                'descripcion' => 'Enviada',
                'idFabricante' => $idFabricante
            ]);
        }

        // 4. Recalcular TODOS los valores
        $subtotal = 0;
        $totalUnidades = 0;
        $productosCalculados = [];

        foreach ($request->productos as $index => $producto) {
            $productoModel = TProducto::withoutGlobalScopes()->findOrFail($producto['id']);
            $precioReal = $productoModel->Precio_producto; 
            
            if (abs($producto['precio'] - $precioReal) > 0.01) {
                Log::warning('Precio alterado detectado', [
                    'producto_id' => $producto['id'],
                    'precio_cliente' => $producto['precio'],
                    'precio_real' => $precioReal
                ]);
                // Opcional: rechazar la orden si hay discrepancia
                throw ValidationException::withMessages(['productos' => 'Precios inconsistentes']);
            }

            // 3. Usar SIEMPRE el precio real para cálculos
            $subtotal += $producto['unidades'] * $precioReal;
            $totalUnidades += $producto['unidades'];
            
            $productosCalculados[] = [
                'id' => $producto['id'],
                'unidades' => $producto['unidades'],
                'precio' => $precioReal,
                'subtotal' => $producto['unidades'] * $precioReal
            ];
        }

        $impuestoDecimal = $request->impuesto / 100;
        $montoConImpuesto = $subtotal * (1 + $impuestoDecimal);

        // 5. Transacción para garantizar consistencia
        DB::beginTransaction();

        try {
            $now = now()->format('Y-m-d H:i:s');

            // 6. Crear la orden con valores recalculados
            $orden = TOrdene::create([
                'idOperador' => Auth::user()->idOperador,
                'idFabricante' => $idFabricante,
                'idPersona' => $request->representante,
                'idpasadopor' => Auth::user()->idPersona,
                'idMayorista' => $request->mayoristas[0]['id'],
                'idPersona_solicitante' => $request->cliente,
                'idpais' => Auth::user()->idpais,
                'costoTotal' => $subtotal,
                'coordenadas_l' => $request->lat,
                'coordenadas_a' => $request->lon,
                'ididioma' => Auth::user()->ididioma,
                'idmoneda' => Auth::user()->idmoneda,
                'tipo_operacion' => 1,
                'impuesto' => $request->impuesto,
                'fechaOrden' => $now,
                'fecha_envio_orden' => $now,
                'idestatus' => $enviada->idestatus,
                'descuento' => 0,
                'TotalUnidades' => $totalUnidades,
                'comentario_entrega' => $request->descripcion ?? '',
            ]);

            // 7. Adjuntar productos con valores recalculados
            foreach ($productosCalculados as $index => $producto) {
                $orden->Productos()->attach($producto['id'], [
                    'idOperador' => Auth::user()->idOperador,
                    'idfactura' => null,
                    'idFabricante' => $idFabricante,
                    'idMayorista' => $request->mayoristas[0]['id'],
                    'iditem_orden' => $index + 1,
                    'item_price' => $producto['precio'],
                    'nombreproducto' => $this->obtenerNombreProducto($producto['id']),
                    'cantidad_solicitada' => $producto['unidades'],
                    'idunidades' => 1,
                    'item_descuento' => 0,
                    'item_total' => $producto['subtotal'],
                    'item_impuesto' => $request->impuesto,
                    'idestatus' => 1,
                ]);
            }

            // 8. Adjuntar todos los mayoristas
            foreach ($request->mayoristas as $index => $mayorista) {
                $orden->Mayoristas()->attach($mayorista['id'], [
                    'nombre_mayorista' => $mayorista['nombre'],
                    'idOperador' => Auth::user()->idOperador,
                    'idFabricante' => $idFabricante,
                    'idpais' => Auth::user()->idpais,
                    'orden_total' => $montoConImpuesto,
                    'impuesto' => $request->impuesto,
                    'idCliente' => $request->cliente,
                    'idmoneda' => Auth::user()->idmoneda,
                    'ididioma' => Auth::user()->ididioma,
                    'item_descuento' => 0,
                    'idestatus' => 1,
                ]);
            }

            DB::commit();

            Log::info('Pedido creado con éxito', [
                'orden_id' => $orden->idorden,
                'subtotal' => $subtotal,
                'total_unidades' => $totalUnidades
            ]);

            return redirect()->route('toma-de-pedidos.index')
                            ->with('success', 'Pedido registrado correctamente.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al procesar pedido', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Error al procesar el pedido. Inténtalo de nuevo.');
        }
    }

    /**
     * Obtiene el nombre del producto para asegurar consistencia
     */
    private function obtenerNombreProducto(string $idProducto): string
    {
        $producto = \App\Models\TProducto::withoutGlobalScopes()->find($idProducto);
        return $producto ? $producto->nombre_producto : "Producto #{$idProducto}";
    }
}