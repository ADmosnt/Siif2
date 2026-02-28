<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrdenResource;
use App\Http\Resources\Api\OrdenDetalleResource;
use App\Models\TOrdene;
use App\Models\TEstatusOrdene;
use App\Models\TPersona;
use App\Models\TProducto;
use App\Services\ReporteDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrdenController extends Controller
{
    protected ReporteDataService $reporteDataService;

    public function __construct(ReporteDataService $reporteDataService)
    {
        $this->reporteDataService = $reporteDataService;
    }

    /**
     * Lista los últimos 10 pedidos del usuario.
     */
    public function index()
    {
        $user = Auth::user();

        $ordenes = TOrdene::where('idPersona', $user->idPersona)
            ->orderBy('idorden', 'desc')
            ->take(10)
            ->get();

        return OrdenResource::collection($ordenes);
    }

    /**
     * Detalle de productos de un pedido.
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ], [
            'required' => 'Se necesita el :attribute',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $orden = TOrdene::withoutGlobalScopes()->find($request->id);
        if (!$orden) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        return new OrdenDetalleResource($orden);
    }

    /**
     * Crear un nuevo pedido.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|string',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.cantidad' => 'required|integer|min:1',
            'cliente' => 'required|string',
            'mayoristas' => 'required|array|min:1',
            'mayoristas.*.id' => 'required|string',
            'mayoristas.*.nombre' => 'required|string',
            'impuesto' => 'required|numeric|min:0',
            'comentario' => 'nullable|string|max:256',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'fecha' => 'nullable|date',
        ], [
            'required' => 'Se necesita el :attribute',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        $idFabricante = $this->reporteDataService->obtenerIdFabricante($user->idPersona);

        if (!$idFabricante) {
            return response()->json(['error' => 'No se pudo determinar el fabricante'], 400);
        }

        $enviada = $this->reporteDataService->obtenerEstadoPorFabricante('Enviada', $idFabricante);
        if (!$enviada) {
            $enviada = TEstatusOrdene::create([
                'idOperador' => $user->idOperador,
                'descripcion' => 'Enviada',
                'idFabricante' => $idFabricante,
            ]);
        }

        $date = $request->fecha ? Carbon::parse($request->fecha) : Carbon::now();

        // Calcular costo total
        $costo = 0;
        $totalUnidades = 0;
        foreach ($request->productos as $prod) {
            $costo += $prod['precio'] * $prod['cantidad'];
            $totalUnidades += $prod['cantidad'];
        }

        try {
            $orden = DB::transaction(function () use ($request, $user, $idFabricante, $enviada, $date, $costo, $totalUnidades) {
                $nuevaOrden = TOrdene::create([
                    'idOperador' => $user->idOperador,
                    'idFabricante' => $idFabricante,
                    'idPersona' => $user->idPersona,
                    'idpasadopor' => $user->idPersona,
                    'idMayorista' => $request->mayoristas[0]['id'],
                    'idPersona_solicitante' => $request->cliente,
                    'idpais' => $user->idpais,
                    'costoTotal' => $costo,
                    'coordenadas_l' => $request->lat,
                    'coordenadas_a' => $request->lon,
                    'ididioma' => $user->ididioma,
                    'idmoneda' => $user->idmoneda,
                    'tipo_operacion' => 1,
                    'impuesto' => $request->impuesto,
                    'fechaOrden' => $date,
                    'fecha_envio_orden' => Carbon::now(),
                    'idestatus' => $enviada->idestatus,
                    'descuento' => $request->mayoristas[0]['descuento'] ?? 0,
                    'TotalUnidades' => $totalUnidades,
                    'comentario_entrega' => $request->comentario ?? '',
                ]);

                foreach ($request->productos as $index => $item) {
                    $precio = $item['precio'] * $item['cantidad'];
                    $producto = TProducto::withoutGlobalScopes()->find($item['id']);

                    $nuevaOrden->productos()->attach($item['id'], [
                        'idOperador' => $user->idOperador,
                        'idfactura' => null,
                        'idFabricante' => $idFabricante,
                        'idMayorista' => $request->mayoristas[0]['id'],
                        'iditem_orden' => $index + 1,
                        'item_price' => $item['precio'],
                        'nombreproducto' => $producto?->nombre_producto ?? "Producto #{$item['id']}",
                        'cantidad_solicitada' => $item['cantidad'],
                        'idunidades' => $item['unidades'] ?? 1,
                        'item_descuento' => $item['descuento'] ?? $request->mayoristas[0]['descuento'] ?? 0,
                        'item_total' => $precio,
                        'item_impuesto' => $request->impuesto,
                        'idestatus' => 1,
                    ]);
                }

                foreach ($request->mayoristas as $mayo) {
                    $nuevaOrden->Mayoristas()->attach($mayo['id'], [
                        'nombre_mayorista' => $mayo['nombre'],
                        'idOperador' => $user->idOperador,
                        'idFabricante' => $idFabricante,
                        'idpais' => $user->idpais,
                        'orden_total' => $costo,
                        'impuesto' => $request->impuesto,
                        'idCliente' => $request->cliente,
                        'idmoneda' => $user->idmoneda,
                        'ididioma' => $user->ididioma,
                        'item_descuento' => $mayo['descuento'] ?? 0,
                        'idestatus' => 1,
                    ]);
                }

                return $nuevaOrden;
            });

            return response()->json([
                'success' => 'Pedido registrado correctamente',
                'idorden' => $orden->idorden,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al crear pedido API', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error al procesar el pedido'], 500);
        }
    }
}
