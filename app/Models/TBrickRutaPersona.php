<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TBrickrutaPersona extends Model
{
    //
    protected $table='t_brick_ruta_personas';
    public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_inicio',
		'fecha_fin'
	];

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idPersona',
		'idzona',
		'idbrick',
		'fecha_inicio',
		'fecha_fin',
		'idestatus'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
