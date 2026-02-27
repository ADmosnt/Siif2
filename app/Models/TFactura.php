<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
/*use App\ScopesOperador;
use RTR\Traits\DML;
use RTR\events\facturaEvent;
use RTR\events\UpdatefacturaEvent;
*/
use App\Models\Scopes\OperadorFabricante;

class TFactura extends Eloquent
{
	protected $primaryKey = 'idfactura';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'fechaFactura' => 'datetime',
		'Impuesto_iva' => 'float',
		'RetencionLegal' => 'float',
		'Descuento' => 'float',
		'Porc_Descuento' => 'float',
		'MontoNeto_Factura' => 'float',
		'Monto_Factura' => 'float'
	];

/*	protected $dates = [
		'fechaFactura',
		'Fecha_pago_factura'
	];*/

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idfactura',
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
		'idestatus',
		'idMayorista_despacho',
		'cantidad_Faltante',
		'cantidad_Pagada'
	];

     
	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/

	protected static function boot()
	{
		parent::boot();
		static::addGlobalScope(new OperadorFabricante);
	}
}
