<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTipoEspecialidade
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idespecialidad
 * @property string $cod_sub_especialidad
 * @property string $descripcion_sub_especialidad
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTipoEspecialidade extends Eloquent
{
	protected $table='t_tipo_especialidad';
	protected $primaryKey = 'idespecialidad';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'cod_sub_especialidad',
		'descripcion_sub_especialidad',
		'idestatus'
	];
}
