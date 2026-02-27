<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class TRolPerfil extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_inicio',
		'fecha_fin'
	];

	protected $fillable = [
		'idOperador',
		'fecha_inicio',
		'fecha_fin',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}

	public function t_role()
	{
		return $this->belongsTo(\App\Models\TRole::class, 'idrol');
	}
}
