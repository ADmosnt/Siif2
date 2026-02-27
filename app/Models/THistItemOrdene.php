<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class THistItemOrdene
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property int $idorden
 * @property string $idfactura
 * @property int $iditem_orden
 * @property string $idproducto
 * @property string $idMayorista
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
 * @property \App\Models\TOrdene $t_ordene
 * @property \App\Models\TProducto $t_producto
 *
 * @package App\modelos
 */
class THistItemOrdene extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idorden' => 'int',
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

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idfactura',
		'idproducto',
		'idMayorista',
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

	public function t_ordene()
	{
		return $this->belongsTo(\App\Models\TOrdene::class, 'idorden');
	}

	public function t_producto()
	{
		return $this->belongsTo(\App\Models\TProducto::class, 'idproducto');
	}
}
