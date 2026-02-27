<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TSucursale
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $cod_sucursal
 * @property string $descripcion_sucursal
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TSucursale extends Eloquent
{
	protected $primaryKey = 'cod_sucursal';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'descripcion_sucursal',
		'idestatus'
	];
}
