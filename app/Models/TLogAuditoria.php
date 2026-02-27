<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TLogAuditoria
 *
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idPersona
 * @property string $cod_modulo
 * @property string $cod_opcion
 * @property string $descripcion_detalle
 * @property \Carbon\Carbon $fecha_detalle
 * @property string $idestatus
 *
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TLogAuditoria extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_detalle'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idPersona',
		'cod_modulo',
		'cod_opcion',
		'descripcion_detalle',
		'idestatus',
        'fecha_detalle'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
