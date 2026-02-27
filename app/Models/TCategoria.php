<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TCategoria extends Model
{
	//
	protected $primaryKey='idcategorias';
    public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'URLImagenCategoria' => 'boolean'
	];

	protected $dates = [
		'FechaCreacion',
		'FechaInicio',
		'FechaFin'
	];

	protected $hidden = ['idOperador'];

	protected $fillable = [
	    'idOperador',
		'idFabricante',
        'idcategorias',
		'NombreCategorias',
		'URLImagenCategoria',
		'FechaCreacion',
		'FechaInicio',
		'FechaFin',
		'idestatus'
	];
	
	public static $validators=[
		'fabricante'=>'required|exists:t_personas,idPersona',
		'id'=>'required|unique:t_categorias,idcategorias|string',
		'descripcion'=>'required|unique:t_categorias,NombreCategorias'
	];
	 
	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
