<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TEspecialidade extends Model
{

	public $timestamps = false;
    public $incrementing = false;

	protected $guarded = ['id'];
	protected $fillable = [
        'id',
        'descripcion_especialidad',
        'estatus'
	];

    public function personas(){
		return $this->hasMany('App\Models\TPersona','id','idactividad_negocio');
	}
}
