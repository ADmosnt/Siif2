<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TLocalizacionPersona
 * 
 * @property int $cod_localizacion
 * @property string $idOperador
 * @property string $idlocalizacion
 * @property string $idPersona
 * @property string $idzona
 * @property string $idbrick
 * @property string $telefono
 * @property string $movil_persona
 * @property string $email
 * @property string $direccion
 * @property string $idestado
 * @property string $idciudad
 * @property string $urbanizacion
 * @property string $zona_postal
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TLocalizacionPersona extends Eloquent
{
	 //use DML;
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'cod_localizacion' => 'int'
	];

	protected $fillable = [
		'cod_localizacion',
		'idOperador',
		'idzona',
		'idbrick',
		'telefono',
		'movil_persona',
		'email',
		'direccion',
		'idestado',
		'idciudad',
		'urbanizacion',
		'zona_postal',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
