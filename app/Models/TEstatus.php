<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


/**
 * Class TEstatus
 * 
 * @property string $idOperador
 * @property string $idestatus
 * @property string $descripcion_estatus
 *
 * @package App\modelos
 */
class TEstatus extends Eloquent
{
	protected $table = 't_estatus';
	protected $primaryKey = 'idestatus';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'descripcion_estatus'
	];

	
	
}
