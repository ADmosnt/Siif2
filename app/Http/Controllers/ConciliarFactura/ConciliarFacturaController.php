<?php

namespace App\Http\Controllers\ConciliarFactura;

use App\Http\Controllers\Controller;
use App\Models\TOrdene;
use App\Models\TProducto;
use App\Models\TEstatusOrdene;
use App\Models\TFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ConciliarFacturaController extends Controller
{
    /**
     * Almacena una nueva factura después de conciliar.
     */
    public function store(Request $request)
    {

        // 1. Validación de datos PRIMARIOS (nunca derivados)
        $validator = Validator::make($request->all(), [
            'norden' => 'required|numeric|exists:t_ordenes,idorden',
            'nfactura' => 'required|string|max:50|unique:t_facturas,idfactura',
            'almacen' => 'required|string|max:255',
            'forma_pago' => 'required|numeric|exists:t_forma_pagos,idformaPago',
            'fecha' => 'required|date',
            'impuesto' => 'required|numeric|min:0|max:100',
            'productos' => 'required|array',
            'productos.*.id' => 'required|string',
            'productos.*.despachadas' => 'required|integer|min:0',
            'productos.*.precio' => 'required|numeric|min:0',
        ], [
            'norden.exists' => 'La orden seleccionada no existe.',
            'nfactura.unique' => 'El número de factura ya está en uso.',
            'productos.*.despachadas.min' => 'Las cantidades despachadas no pueden ser negativas.',
        ]);

        if ($validator->fails()) {
        Log::debug('Validation failed - mensaje: ' . $validator->errors()->first());
        return back()->withInput()->with('error', $validator->errors()->first());
    }
        

        $orden = TOrdene::withoutGlobalScopes()->find($request->norden);
        $impuestoReal = $orden->impuesto;

        if (abs($request->impuesto - $impuestoReal) > 0.01) {
            Log::warning('Impuesto alterado en conciliación', [
                'impuesto_cliente' => $request->impuesto,
                'impuesto_real' => $impuestoReal
            ]);
            return redirect()->back()->withInput()->with('error', 'Tasa de impuesto inválida');
}
        // 2. Verificar estado de la orden
        $estatus = TEstatusOrdene::withoutGlobalScopes()
            ->where('idFabricante', $orden->idFabricante)
            ->where('descripcion', 'Facturada')
            ->first();

        if (!$estatus) {
            Log::error('Estado "Facturada" no encontrado para fabricante', ['idFabricante' => $orden->idFabricante]);
            // CORRECCIÓN: Redirigir con mensaje flash
            return redirect()->back()->withInput()->with('error', 'Configuración de sistema incompleta');
        }

        if ($orden->idfactura || $orden->idestatus == $estatus->idestatus) {
            // CORRECCIÓN: Redirigir con mensaje flash
            return redirect()->back()->withInput()->with('error', 'Esta orden ya fue facturada');
        }

        if ($orden->idestatus == TEstatusOrdene::where('descripcion', 'Anulada')->value('idestatus')) {
            // CORRECCIÓN: Redirigir con mensaje flash
            return redirect()->back()->withInput()->with('error', 'Esta orden fue anulada previamente');
        }

        // 3. Recalcular TODOS los valores en el backend (seguro)
        $subtotal = 0;
        $totalConciliadas = 0;
        $totalFaltantes = 0;
        $productosActualizados = [];

        foreach ($request->productos as $producto) {
            
            $productoModel = TProducto::withoutGlobalScopes()->findOrFail($producto['id']);
            $precioReal = $productoModel->Precio_producto;

            if (abs($producto['precio'] - $precioReal) > 0.01) {
                Log::warning('Precio alterado en conciliación', [
                    'producto_id' => $producto['id'],
                    'precio_cliente' => $producto['precio'],
                    'precio_real' => $precioReal
                ]);
                
                return redirect()->back()->withInput()->with('error', "Precios inconsistentes en producto {$producto['id']}");
            }

            $pivotData = $orden->productos()
                ->where('t_item_ordenes.idproducto', $producto['id']) // <-- Especificar tabla
                ->first();
                
            if (!$pivotData) {
                return redirect()->back()->withInput()->with('error', "Producto {$producto['id']} no encontrado en la orden");
            }
            $cantidadOriginal = $pivotData->pivot->cantidad_solicitada ?? 0;
            $faltantes = $cantidadOriginal - $producto['despachadas'];
            $subtotal += $producto['despachadas'] * $precioReal;
            $totalConciliadas += $producto['despachadas'];
            $totalFaltantes += $faltantes;
            
            $productosActualizados[] = [
                'id' => $producto['id'],
                'conciliada' => $producto['despachadas'],
                'faltantes' => $faltantes,
                'precio' => $precioReal
            ];
        }

        // 4. Calcular valores finales
        $impuestoDecimal = $impuestoReal / 100;
        $montoNeto = $subtotal;
        $montoConImpuesto = $subtotal * (1 + $impuestoDecimal);
        $descuento = 0; // Aquí aplicarías lógica de descuentos si existiera

        // 5. Transacción para garantizar consistencia
        DB::beginTransaction();

        try {
            // 6. Crear la factura
            $factura = TFactura::create([
                'idOperador' => $orden->idOperador,
                'idFabricante' => $orden->idFabricante,
                'idfactura' => $request->nfactura,
                'idordenes' => $orden->idorden,
                'idPersona_solicitante' => $orden->idPersona_solicitante,
                'idformaPago' => $request->forma_pago,
                'fechaFactura' => $request->fecha,
                'Fecha_pago_factura' => $request->fecha,
                'DireccionCliente' => $orden->cliente->direccion_domicilio ?? '',
                'ReferenciaFactura' => $orden->idorden,
                'DescripcionFactura' => 'Factura de conciliación',
                'Impuesto_iva' => $request->impuesto,
                'Descuento' => $descuento,
                'Porc_Descuento' => 0,
                'MontoNeto_Factura' => $montoNeto,
                'Monto_Factura' => $montoConImpuesto,
                'idMayorista_despacho' => $orden->idMayorista,
                'idestatus' => 1, // Estado inicial de la factura
                'cantidad_Faltante' => $totalFaltantes,
                'cantidad_Pagada' => $totalConciliadas,
            ]);

            // 7. Actualizar productos en la orden
            foreach ($productosActualizados as $producto) {
                DB::table('t_item_ordenes')
                    ->where('idorden',    $orden->idorden)
                    ->where('idproducto', $producto['id'])
                    ->update([
                        'cantidad_conciliada' => $producto['conciliada'],
                        'cantidad_faltante'   => $producto['faltantes'],
                        'item_price'          => $producto['precio'],
                        'item_total'          => $producto['conciliada'] * $producto['precio'],
                    ]);
            }

            // 8. Actualizar la orden
            $orden->update([
                'idfactura' => $request->nfactura,
                'idestatus' => $estatus->idestatus,
                'costoTotal' => $montoNeto, // Actualizar con valor recalculado
                'registro_entrega' => now(),
                'fecha_entrega' => now(),
            ]);

            // 9. Generar nueva orden si hay productos faltantes
            $nuevaOrdenId = null;
            if ($totalFaltantes > 0) {
                $estatusEnviada = TEstatusOrdene::withoutGlobalScopes()
                    ->where('idFabricante', $orden->idFabricante)
                    ->where('descripcion', 'Enviada')
                    ->first();

                if ($estatusEnviada) {
                    $costoFaltantes = 0;
                    foreach ($productosActualizados as $p) {
                        $costoFaltantes += $p['faltantes'] * $p['precio'];
                    }

                    $nuevaOrden = TOrdene::create([
                        'idOperador' => $orden->idOperador,
                        'idFabricante' => $orden->idFabricante,
                        'idPersona' => $orden->idPersona,
                        'idpasadopor' => $orden->idpasadopor,
                        'idMayorista' => $orden->idMayorista,
                        'idPersona_solicitante' => $orden->idPersona_solicitante,
                        'idpais' => $orden->idpais,
                        'ididioma' => $orden->ididioma,
                        'idmoneda' => $orden->idmoneda,
                        'tipo_operacion' => $orden->tipo_operacion,
                        'impuesto' => $orden->impuesto,
                        'costoTotal' => $costoFaltantes,
                        'fechaOrden' => now(),
                        'fecha_envio_orden' => now(),
                        'idestatus' => $estatusEnviada->idestatus,
                        'TotalUnidades' => $totalFaltantes,
                        'comentario_entrega' => "Faltantes de orden #{$orden->idorden} (Factura: {$request->nfactura})",
                    ]);

                    $itemOrden = 0;
                    foreach ($productosActualizados as $p) {
                        if ($p['faltantes'] > 0) {
                            $itemOrden++;
                            $productoModel = TProducto::withoutGlobalScopes()->find($p['id']);
                            $nuevaOrden->productos()->attach($p['id'], [
                                'idOperador' => $orden->idOperador,
                                'idFabricante' => $orden->idFabricante,
                                'iditem_orden' => $itemOrden,
                                'item_price' => $p['precio'],
                                'cantidad_solicitada' => $p['faltantes'],
                                // t_item_ordenes.nombreproducto es varchar(45)
                                'nombreproducto' => substr($productoModel->nombre_producto ?? '', 0, 45),
                                'item_descuento' => 0,
                                'item_total' => $p['faltantes'] * $p['precio'],
                                'item_impuesto' => $orden->impuesto,
                                'idestatus' => 1,
                            ]);
                        }
                    }

                    // Copiar mayoristas de la orden original
                    $mayoristasOriginales = $orden->Mayoristas()->withoutGlobalScopes()->get();
                    foreach ($mayoristasOriginales as $may) {
                        $pivotData = $may->pivot;
                        $nuevaOrden->Mayoristas()->attach($may->idPersona, [
                            'nombre_mayorista' => $pivotData->nombre_mayorista,
                            'item_descuento' => $pivotData->item_descuento ?? 0,
                            'orden_total' => $costoFaltantes,
                            'idOperador' => $orden->idOperador,
                            'idFabricante' => $orden->idFabricante,
                            'idpais' => $pivotData->idpais ?? null,
                            'idCliente' => $pivotData->idCliente ?? null,
                            'ididioma' => $pivotData->ididioma ?? null,
                            'idmoneda' => $pivotData->idmoneda ?? null,
                            'impuesto' => $orden->impuesto,
                            'idestatus' => $estatusEnviada->idestatus,
                        ]);
                    }

                    $nuevaOrdenId = $nuevaOrden->idorden;

                    Log::info('Nueva orden generada por faltantes', [
                        'orden_original' => $orden->idorden,
                        'nueva_orden' => $nuevaOrdenId,
                        'total_faltantes' => $totalFaltantes,
                        'costo_faltantes' => $costoFaltantes,
                    ]);
                }
            }

            DB::commit();

            // No se conserva 'orden' en la redirección: la orden recién
            // facturada ya no debe seguir seleccionada, y mandarla de vuelta
            // obligaba al frontend a disparar una segunda navegación para
            // limpiarla (esa segunda navegación era la que el navegador
            // cancelaba con NS_BINDING_ABORTED).
            if ($nuevaOrdenId) {
                return redirect()->route('consolidar.index', [
                    'fabricante' => $request->input('fabricante'),
                ])->with('success', "Factura creada. Se genero la orden #{$nuevaOrdenId} con los productos faltantes.");
            }

            return redirect()->route('consolidar.index', [
                'fabricante' => $request->input('fabricante'),
            ])->with('success', 'La orden ha sido conciliada al completo');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al conciliar factura', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            // CORRECCIÓN: Redirigir con mensaje flash
            return redirect()->back()->withInput()->with('error', 'Hubo un error al procesar la factura. Por favor, inténtelo de nuevo.');
        }
    }
}