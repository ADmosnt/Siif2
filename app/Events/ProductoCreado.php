<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\TProducto;

class ProductoCreado
{
    use Dispatchable, SerializesModels;

    public $producto;

    public function __construct(TProducto $producto)
    {
        $this->producto = $producto;
    }
}