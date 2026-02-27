<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class THistOrdene
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idorden
 * @property string $idPersona
 * @property string $idMayorista
 * @property string $idMayorista2
 * @property string $idPersona_solicitante
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $tipo_operacion
 * @property float $impuesto
 * @property float $comision
 * @property float $costoTotal
 * @property float $descuento
 * @property \Carbon\Carbon $fechaOrden
 * @property \Carbon\Carbon $fecha_envio_orden
 * @property string $registro_entrega
 * @property \Carbon\Carbon $fecha_entrega
 * @property string $firma_entrega
 * @property string $comentario_entrega
 * @property string $Id_tipo_incidente
 * @property string $idfactura
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class THistOrdene extends Eloquent
{
	protected $primaryKey = 'idorden';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'impuesto' => 'float',
		'comision' => 'float',
		'costoTotal' => 'float',
		'descuento' => 'float'
	];

	protected $dates = [
		'fechaOrden',
		'fecha_envio_orden',
		'fecha_entrega'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idPersona',
		'idMayorista',
		'idMayorista2',
		'idPersona_solicitante',
		'idpais',
		'ididioma',
		'idmoneda',
		'tipo_operacion',
		'impuesto',
		'comision',
		'costoTotal',
		'descuento',
		'fechaOrden',
		'fecha_envio_orden',
		'registro_entrega',
		'fecha_entrega',
		'firma_entrega',
		'comentario_entrega',
		'Id_tipo_incidente',
		'idfactura',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
