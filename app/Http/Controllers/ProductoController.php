<?php

namespace App\Http\Controllers;

use App\Models\TProducto;
use App\Http\Requests\ProductoRequest;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function store(ProductoRequest $request): JsonResponse
    {
        $producto = TProducto::create($request->validated());
        return response()->json(new ProductoResource($producto), 201);
    }

    public function show(TProducto $producto): JsonResponse
    {
        return response()->json(new ProductoResource($producto));
    }

    public function update(ProductoRequest $request, TProducto $producto): JsonResponse
    {
        $producto->update($request->validated());
        return response()->json(new ProductoResource($producto));
    }

}