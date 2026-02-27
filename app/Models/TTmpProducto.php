<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpProducto
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idProducto
 * @property string $descripcion_producto
 * @property string $presentacion
 * @property float $precio_fabrica
 * @property string $idMayorista
 * @property string $Mayorista
 * @property float $descuento
 * @property string $Alta_Baja_Update
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpProducto extends Eloquent
{
	protected $table='t_tmp_productos';
	protected $primaryKey='idProducto';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'precio_fabrica' => 'float',
		'descuento' => 'float'
	];

	protected $fillable = [
		'idOperador' ,
		'idfabricante' ,
		'idProducto',
		'descripcion_producto',
		'presentacion',
		'precio_fabrica',
		'idMayorista',
		'Mayorista',
		'descuento',
		'Alta_Baja_Update',
		'idestatus'
	];
}
