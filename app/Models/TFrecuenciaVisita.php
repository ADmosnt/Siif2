<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TFrecuenciaVisita extends Model
{
    protected $primaryKey = 'idfrecuencia';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'descripcion_frecuencia_visitas',
		'idestatus'
	];
	public static $validators=[
		'descripcion'=>'required|unique:t_frecuencia_visitas,descripcion_frecuencia_visitas',
		'id'=>'required|unique:t_frecuencia_visitas,idfrecuencia'
	];
	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/
	
	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
	}
}
