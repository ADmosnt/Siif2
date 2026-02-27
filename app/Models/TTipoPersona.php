<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TTipoPersona extends Model
{
    //
	protected $table ='t_tipo_personas';
    protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'descripcion_tipo_persona',
		'idestatus'
	];
}
