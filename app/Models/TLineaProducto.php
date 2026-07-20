<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TLineaProducto extends Model
{
    //
    protected $primaryKey = 'id';
	protected $table = 't_linea_productos';
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
