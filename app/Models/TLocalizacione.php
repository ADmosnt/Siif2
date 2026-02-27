<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TLocalizacione
 * 
 * @property string $idOperador
 * @property string $idlocalizacion
 * @property string $descripcion_localizacion
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TLocalizacione extends Eloquent
{
	 //use DML;
	protected $primaryKey = 'idlocalizacion';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'descripcion_localizacion',
		'idestatus'
	];
}
