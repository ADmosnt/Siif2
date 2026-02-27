<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TRedesSociale
 * 
 * @property string $idOperador
 * @property string $cod_redes_sociales
 * @property string $descripcion_redes_sociales
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TRedesSociale extends Eloquent
{
	protected $primaryKey = 'cod_redes_sociales';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'cod_redes_sociales',
		'descripcion_redes_sociales',
		'idestatus'
	];
}
