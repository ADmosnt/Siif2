<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TAuditable
 * 
 * @property string $idOperador
 * @property string $id_auditables
 * @property string $descripcion_auditables
 * @property \Carbon\Carbon $fecha_auditables
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TAuditable extends Eloquent
{
	protected $primaryKey = 'id_auditables';
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_auditables'
	];

	protected $fillable = [
		'idOperador',
		'descripcion_auditables',
		'fecha_auditables',
		'idestatus'
	];
}
