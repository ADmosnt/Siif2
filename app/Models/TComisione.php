<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TComisione
 * 
 * @property string $idPersona
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idcomisiones
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $descripcion_comisiones
 * @property string $tipocomisiones
 * @property float $porcentajes
 * @property float $mintarifacomisiones
 * @property float $maxtarifacomisiones
 * @property \Carbon\Carbon $fechaRegistro
 * @property \Carbon\Carbon $fechaModificacion
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TComisione extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'porcentajes' => 'float',
		'mintarifacomisiones' => 'float',
		'maxtarifacomisiones' => 'float'
	];

	protected $dates = [
		'fechaRegistro',
		'fechaModificacion'
	];

	protected $fillable = [
		'idPersona',
		'idpais',
		'ididioma',
		'idmoneda',
		'descripcion_comisiones',
		'tipocomisiones',
		'porcentajes',
		'mintarifacomisiones',
		'maxtarifacomisiones',
		'fechaRegistro',
		'fechaModificacion',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
