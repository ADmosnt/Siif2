<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TArchivosRecibido
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idPersona
 * @property string $idArchivo
 * @property \Carbon\Carbon $fecha_recepcion
 * @property \Carbon\Carbon $fecha_procesamiento
 * @property string $mes_year
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TArchivosRecibido extends Eloquent
{  
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_recepcion',
		'fecha_procesamiento'
	];

	protected $fillable = [
		'idOperador', 
		'idfabricante' ,
		'idPersona' ,
		'idArchivo',
		'idPersona',
		'fecha_recepcion',
		'fecha_procesamiento',
		'mes_year',
		'idestatus'
	];
}
