<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpEsquemaPromocionale
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idesqprom
 * @property string $idProducto
 * @property string $linea
 * @property string $idranking
 * @property float $cantidad_desde
 * @property float $cantidad_hasta
 * @property float $monto_desde
 * @property float $monto_hasta
 * @property string $cantidad_promocion
 * @property float $descuento
 * @property string $Alta_Baja_Update
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpEsquemaPromocionale extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'cantidad_desde' => 'float',
		'cantidad_hasta' => 'float',
		'monto_desde' => 'float',
		'monto_hasta' => 'float',
		'descuento' => 'float'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idesqprom',
		'linea',
		'idranking',
		'cantidad_desde',
		'cantidad_hasta',
		'monto_desde',
		'monto_hasta',
		'cantidad_promocion',
		'descuento',
		'Alta_Baja_Update',
		'idestatus'
	];
}
