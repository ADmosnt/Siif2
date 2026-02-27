<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class THistProducto
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idproducto
 * @property string $idPersona
 * @property string $idMayorista
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $idlinea_producto
 * @property string $nombre_producto
 * @property string $descripcion_producto
 * @property string $principio_producto
 * @property float $Precio_producto
 * @property float $Descuento_producto
 * @property string $idcategorias
 * @property string $idunidades
 * @property float $cantidad_producto_existente
 * @property \Carbon\Carbon $fechaRegistro_producto
 * @property \Carbon\Carbon $fechaExpedicion_producto
 * @property \Carbon\Carbon $fechaVencimiento_producto
 * @property string $dias_publicación
 * @property string $idpromocion
 * @property \Carbon\Carbon $FechaInicioPublicacion
 * @property \Carbon\Carbon $FechaFinPublicacion
 * @property string $estatus_producto
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class THistProducto extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Precio_producto' => 'float',
		'Descuento_producto' => 'float',
		'cantidad_producto_existente' => 'float'
	];

	protected $dates = [
		'fechaRegistro_producto',
		'fechaExpedicion_producto',
		'fechaVencimiento_producto',
		'FechaInicioPublicacion',
		'FechaFinPublicacion'
	];

	protected $fillable = [
		'idOperador',
		'idPersona',
		'idpais',
		'ididioma',
		'idmoneda',
		'idlinea_producto',
		'nombre_producto',
		'descripcion_producto',
		'principio_producto',
		'Precio_producto',
		'Descuento_producto',
		'idcategorias',
		'idunidades',
		'cantidad_producto_existente',
		'fechaRegistro_producto',
		'fechaExpedicion_producto',
		'fechaVencimiento_producto',
		'dias_publicación',
		'idpromocion',
		'FechaInicioPublicacion',
		'FechaFinPublicacion',
		'estatus_producto'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
