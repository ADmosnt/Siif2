<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TListanegra
 * 
 * @property string $idOperador
 * @property string $idlistanegra
 * @property string $idPersona
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $Causas_listanegra
 * @property string $fecha_registro
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TListanegra extends Eloquent
{
	protected $table = 't_listanegra';
	protected $primaryKey='idlistanegra';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idpais',
		'ididioma',
		'idmoneda',
		'idlistanegra',
		'idPersona',
		'Causas_listanegra',
		'fecha_registro',
		'idestatus'
	];

	  public static $validators=[
		  'CI'=>'required|exists:t_personas,idPersona',
		  'id'=>'required|unique:t_listanegra,idlistanegra',
		  'fabricante'=>'required|exists:t_personas,idPersona',
		  'descripcion'=>'required'
	  ];
	

	public function personas()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
