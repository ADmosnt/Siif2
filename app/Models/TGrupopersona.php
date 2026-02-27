<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TGrupopersona extends Model
{
    //
    protected $primaryKey = 'idgrupo_persona';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idgrupo_persona',
		'descripciongrupoPersona',
		'idestatus'
	];
	
	
	public function Personas(){
		return $this->hasMany('App\Models\TPersona','idgrupo_persona','idgrupo_persona');
	}

	public function templatesNotificaciones(){
		return $this->belongsToMany(TMensajeNotificable::class,'t_grupos_notificaciones','idgrupo_persona','idgrupo_origen');
	}
   public function templatesDestNotificaciones(){
	return $this->belongsToMany(TMensajeNotificable::class,'t_grupos_notificaciones','idgrupo_persona','idgrupo_destino');
   }
}
