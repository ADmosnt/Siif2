<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TDiasVisita extends Model
{
    protected $table='t_dias_visitas';
	protected $primaryKey='iddias_visita';
	public $incrementing = false;
	public $timestamps = false;

	protected $hidden = [
		'idpais',
		'idOperador',
        'idestatus'
	];
	protected $fillable = [
		'idFabricante',
        'idOperador',
		'idpais',
		'iddias_visita',
		'descripcion_dias_visita',
		'idestatus'
	];


	public function personas(){
		return $this->belongsToMany(\App\Models\TPersona::class,'t_persona_dias','iddias_visita','idPersona');
	}

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
