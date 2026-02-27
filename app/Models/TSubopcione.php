<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TSubopcione
 * 
 * @property string $idSubopciones
 * @property string $descripcion_Subopciones
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TSubopcione extends Eloquent
{
	protected $primaryKey = 'idSubopciones';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'descripcion_Subopciones',
		'idestatus'
	];
}
