<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TTipoLocalizacione extends Model
{
    protected $primaryKey = 'cod_tipo_localizacion';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'cod_tipo_localizacion',
		'idFabricante',
		'descripcion_tipo_localizacion',
		'idestatus'
	];
   
	public static $validators=[
		'fabricante'=>'required|exists:t_personas,idPersona',
		'id'=>'required|unique:t_tipo_localizaciones,cod_tipo_localizacion',
		'descripcion'=>'required|unique:t_tipo_localizaciones,descripcion_tipo_localizacion'
	];
	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
