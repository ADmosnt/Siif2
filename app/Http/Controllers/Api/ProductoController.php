<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductoResource;
use App\Models\TProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    /**
     * Lista todos los productos activos (excluye muestras).
     */
    public function index()
    {
        $user = Auth::user();

        $productos = TProducto::where('estatus_producto', 1)
            ->where('idcategorias', '!=', 'MUES')
            ->where('idfabricante', $user->idFabricante)
            ->orderBy('nombre_producto')
            ->get();

        return ProductoResource::collection($productos);
    }

    /**
     * Crea un nuevo producto.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        $producto = TProducto::create([
            'idOperador' => $user->idOperador,
            'idfabricante' => $user->idFabricante,
            'idproducto' => $request->idproducto ?? uniqid('PROD_'),
            'nombre_producto' => $request->nombre,
            'descripcion_producto' => $request->descripcion ?? '',
            'Precio_producto' => $request->precio,
            'cantidad_producto_existente' => $request->cantidad,
            'estatus_producto' => 1,
            'idpais' => $user->idpais,
            'ididioma' => $user->ididioma,
            'idmoneda' => $user->idmoneda,
        ]);

        return response()->json(['success' => 'Producto creado', 'idproducto' => $producto->idproducto]);
    }

    /**
     * Modifica un producto existente.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idproducto' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $producto = TProducto::find($request->idproducto);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        if ($request->has('cantidad')) $producto->cantidad_producto_existente = $request->cantidad;
        if ($request->has('precio')) $producto->Precio_producto = $request->precio;
        if ($request->has('descuento')) $producto->descuento_producto = $request->descuento;
        if ($request->has('nombre')) $producto->nombre_producto = $request->nombre;

        $producto->save();

        return response()->json(['success' => 'Producto actualizado']);
    }
}
