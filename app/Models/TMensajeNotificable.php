<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TMensajeNotificable extends Model
{
    //
     protected $table='t_mensajes_notificables';
     public $incrementing=true;

     protected $fillable=[
        'idOperador', 
        'idfabricante',
        'mensaje' ,
        'idtipo' ,
        'idestatus'
     ];

     public function tipo(){
         return $this->belongsTo('App\Models\TTipoNotificacion','idtipo');
     }

     public function gruposOrigen(){
         return $this->belongsToMany(TGrupopersona::class,'t_grupos_notificaciones','idnotificacion','idgrupo_origen');
     }

     public function gruposDestino(){
        return $this->belongsToMany(TGrupopersona::class,'t_grupos_notificaciones','idnotificacion','idgrupo_destino');
     }

    
}
