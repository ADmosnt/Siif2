<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class THistFactura
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idfactura
 * @property string $idordenes
 * @property string $idPersona_solicitante
 * @property string $idformaPago
 * @property \Carbon\Carbon $fechaFactura
 * @property \Carbon\Carbon $Fecha_pago_factura
 * @property string $DireccionCliente
 * @property string $ReferenciaFactura
 * @property string $DescripcionFactura
 * @property string $Banco
 * @property string $NumeroCuentaReferencia
 * @property float $Impuesto_iva
 * @property float $RetencionLegal
 * @property float $Descuento
 * @property float $Porc_Descuento
 * @property float $MontoNeto_Factura
 * @property float $Monto_Factura
 * @property string $idestatus
 *
 * @package App\modelos
 */
class THistFactura extends Eloquent
{
	protected $table = 't_hist_factura';
	protected $primaryKey = 'idfactura';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Impuesto_iva' => 'float',
		'RetencionLegal' => 'float',
		'Descuento' => 'float',
		'Porc_Descuento' => 'float',
		'MontoNeto_Factura' => 'float',
		'Monto_Factura' => 'float'
	];

	protected $dates = [
		'fechaFactura',
		'Fecha_pago_factura'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idordenes',
		'idPersona_solicitante',
		'idformaPago',
		'fechaFactura',
		'Fecha_pago_factura',
		'DireccionCliente',
		'ReferenciaFactura',
		'DescripcionFactura',
		'Banco',
		'NumeroCuentaReferencia',
		'Impuesto_iva',
		'RetencionLegal',
		'Descuento',
		'Porc_Descuento',
		'MontoNeto_Factura',
		'Monto_Factura',
		'idestatus'
	];
}
