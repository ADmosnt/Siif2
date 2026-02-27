<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TSubEspecialidad
 * 
 * @property string $idOperador
 * @property string $idactividad_negocio
 * @property string $descripcion_actividad_negocio
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TSubEspecialidad extends Eloquent
{
	protected $table = 't_sub_especialidad';
	public $timestamps = false;
	protected $primaryKey = 'id';
	protected $fillable = [
	    'id',
		'descripcion_actividad_negocio',
		'idestatus'
	];
}
