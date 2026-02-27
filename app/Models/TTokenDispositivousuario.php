<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTokenDispositivousuario
 * 
 * @property string $idOperador
 * @property string $idtokendispositivoUsuario
 * @property string $idPersona
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $deviceToken
 * @property string $insignia
 * @property string $tipo
 * @property string $modo
 * @property \Carbon\Carbon $fechaHora
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TTokenDispositivousuario extends Eloquent
{
	protected $primaryKey = 'idtokendispositivoUsuario';
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fechaHora'
	];

	protected $fillable = [
		'idOperador',
		'idPersona',
		'ididioma',
		'idmoneda',
		'deviceToken',
		'insignia',
		'tipo',
		'modo',
		'fechaHora',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
