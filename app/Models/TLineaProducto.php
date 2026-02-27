<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TLineaProducto extends Model
{
    //
    protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'id',
		'alias',
		'descripcion_linea_producto',
		'idestatus'
	];

}
