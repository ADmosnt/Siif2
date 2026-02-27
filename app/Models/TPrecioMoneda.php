<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TPrecioMoneda
 * 
 * @property string $idOperador
 * @property string $idprecio_moneda
 * @property string $idpais
 * @property string $idmoneda
 * @property float $tasa_precio_moneda
 * @property \Carbon\Carbon $fecha_precio
 * @property \Carbon\Carbon $fecha_precio_registro
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TPrecioMoneda extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tasa_precio_moneda' => 'float'
	];

	protected $dates = [
		'fecha_precio',
		'fecha_precio_registro'
	];

	protected $fillable = [
		'idOperador',
		'idpais',
		'idmoneda',
		'tasa_precio_moneda',
		'fecha_precio_registro',
		'idestatus'
	];
}
