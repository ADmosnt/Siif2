<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TTipoIncidente extends Model
{
	    protected $table = 't_tipo_incidentes';
    protected $primaryKey = 'idtipo_incidentes';
	public $incrementing = true;
	public $timestamps = false;

	protected $hidden =['idOperador','idestatus'];

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idtipo_incidentes',
		'descripcion_tipo_incidentes',
		'idestatus'
	];

	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
