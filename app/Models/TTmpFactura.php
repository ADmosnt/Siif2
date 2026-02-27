<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpFactura
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idfactura
 * @property string $idorden
 * @property string $idMayorista
 * @property float $impuesto
 * @property float $comision
 * @property float $costoTotal
 * @property float $descuento
 * @property \Carbon\Carbon $fechaFactura
 * @property string $idtipo_incidentes
 * @property int $iditem_orden
 * @property string $idproducto
 * @property string $idunidades
 * @property int $cantidad_solicitada
 * @property int $cantidad_facturada
 * @property int $cantidad_conciliada
 * @property int $cantidad_faltante
 * @property float $item_price
 * @property float $item_descuento
 * @property float $item_impuesto
 * @property float $item_total
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpFactura extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'impuesto' => 'float',
		'comision' => 'float',
		'costoTotal' => 'float',
		'descuento' => 'float',
		'iditem_orden' => 'int',
		'cantidad_solicitada' => 'int',
		'cantidad_facturada' => 'int',
		'cantidad_conciliada' => 'int',
		'cantidad_faltante' => 'int',
		'item_price' => 'float',
		'item_descuento' => 'float',
		'item_impuesto' => 'float',
		'item_total' => 'float'
	];

	protected $dates = [
		'fechaFactura'
	];

	protected $fillable = [
		'idorden',
		'idMayorista',
		'impuesto',
		'comision',
		'costoTotal',
		'descuento',
		'fechaFactura',
		'idtipo_incidentes',
		'iditem_orden',
		'idproducto',
		'idunidades',
		'cantidad_solicitada',
		'cantidad_facturada',
		'cantidad_conciliada',
		'cantidad_faltante',
		'item_price',
		'item_descuento',
		'item_impuesto',
		'item_total',
		'idestatus'
	];
}
