<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TModulo
 * 
 * @property string $idOperador
 * @property string $idmodulo
 * @property string $descripcion_modulo
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TModulo extends Eloquent
{
	protected $primaryKey = 'idmodulo';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'descripcion_modulo',
		'idestatus'
	];
}
