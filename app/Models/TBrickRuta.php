<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TBrickRuta extends Model
{
    protected $table='t_brick_rutas';
	protected $primaryKey='idbrick';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idbrick' ,
		'Descripcion' ,
		'idzona'  ,
		'idestatus',
	];
	 public function zona(){
		 return $this->belongsTo(\App\Models\TZona::class,'idzona','idzona');
	 }

    public function personas(){
        return $this->belongsToMany(\App\Models\TPersona::class,'t_brick_ruta_personas','idbrick','idPersona');
    }
	
	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/

	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
