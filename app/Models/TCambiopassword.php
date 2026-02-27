<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TCambiopassword
 * 
 * @property string $idOperador
 * @property string $idPersona
 * @property string $password
 * @property string $ConfirmarCambioPassword
 * @property \Carbon\Carbon $FechaCambiopassword
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TCambiopassword extends Eloquent
{
	protected $table = 't_cambiopassword';
	protected $primaryKey = 'idPersona';
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'FechaCambiopassword'
	];

	protected $hidden = [
		'password',
		'FechaCambiopassword'
	];

	protected $fillable = [
		'idOperador',
		'password',
		'ConfirmarCambioPassword',
		'FechaCambiopassword',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
