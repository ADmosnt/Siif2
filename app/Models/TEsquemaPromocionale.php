<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Models\Scopes\OperadorFabricante;

class TEsquemaPromocionale extends Eloquent
{
    protected $primaryKey='idesqprom';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'cantidad_desde' => 'float',
        'cantidad_hasta' => 'float',
        'monto_desde' => 'float',
        'monto_hasta' => 'float',
        'porcentaje' => 'float',
        'cantidad' => 'float'
    ];

    protected $fillable = [
        'idOperador' ,
        'idFabricante' ,
        'idesqprom' ,
        'idProducto' ,
        'idMayorista',
        'cantidad_desde' ,
        'cantidad_hasta' ,
        'monto_desde' ,
        'monto_hasta' ,
        'cantidad' ,
        'porcentaje',
        'Alta_Baja_Update' ,
        'idestatus'
    ];

    public function producto()
    {
        return $this->belongsTo(\App\Models\TProducto::class, 'idProducto', 'idproducto');
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}