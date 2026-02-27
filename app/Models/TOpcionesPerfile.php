<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TOpcionesPerfile
 * 
 * @property string $idOperador
 * @property string $idperfil
 * @property string $opciones_perfil
 *
 * @package App\modelos
 */
class TOpcionesPerfile extends Eloquent
{
	protected $primaryKey = 'idperfil';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'opciones_perfil'
	];
}
