<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TLog
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idPersona
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property int $id_Dispositivo_generoModifcacion
 * @property string $tipoLog
 * @property string $PersonaNotificada
 * @property string $CorreoNotificacionMensaje
 * @property string $RegistroOcurrido
 * @property \Carbon\Carbon $fecha_registro
 * @property \Carbon\Carbon $fecha_envio
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TLog extends Eloquent
{
	protected $primaryKey = 'idPersona';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_Dispositivo_generoModifcacion' => 'int'
	];

	protected $dates = [
		'fecha_registro',
		'fecha_envio'
	];

	protected $fillable = [
		'idOperador',
		'idPersona',
		'idfabricante',
		'idpais',
		'ididioma',
		'idmoneda',
		'id_Dispositivo_generoModifcacion',
		'tipoLog',
		'PersonaNotificada',
		'CorreoNotificacionMensaje',
		'RegistroOcurrido',
		'fecha_registro',
		'fecha_envio',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}


}
