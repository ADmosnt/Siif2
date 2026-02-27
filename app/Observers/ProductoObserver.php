<?php

namespace App\Observers;

use App\Models\TProducto;
use App\Events\ProductoCreado;
use App\Events\ProductoActualizado;
use Illuminate\Support\Facades\Log;

class ProductoObserver
{
    public function created(TProducto $producto)
    {
        // Disparar evento personalizado
        event(new ProductoCreado($producto));
        
        // Lógica adicional directa si es necesario
        Log::info("Nuevo producto registrado: {$producto->nombre_producto}");
    }

    public function updated(TProducto $producto)
    {
        // Disparar evento personalizado
        event(new ProductoActualizado($producto));
        
    }
}