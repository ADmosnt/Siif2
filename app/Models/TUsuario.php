<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;


use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class TUsuario
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idPersona
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $nombre_usuario
 * @property string $email
 * @property string $password
 * @property string $idperfil
 * @property string $dias_vigencia_clave
 * @property \Carbon\Carbon $fecha_inicio
 * @property \Carbon\Carbon $fecha_finalizo
 * @property \Carbon\Carbon $fecha_ultimo_ingreso
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TUsuario  extends Authenticatable
{
    use Notifiable, HasApiTokens;

    public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_inicio',
		'fecha_finalizo',
		'fecha_ultimo_ingreso'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idpais',
		'ididioma',
		'idmoneda',
		'email',
		'password',
		'idperfil',
		'dias_vigencia_clave',
		'fecha_inicio',
		'fecha_finalizo',
		'fecha_ultimo_ingreso',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
