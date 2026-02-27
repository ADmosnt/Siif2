<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TNotificacion extends Model
{
    //
     protected $table='t_notificaciones';
     protected $primaryKey='idNotificacion';
     public $timestamps = false;
     public $incrementing=true;
       protected $date=[
           'fecha_registro'
       ];
     protected $fillable=[
        'idOperador',
        'idFabricante',
        'idPersona',
        'idFabricante',
        'idtipo',
        'descripcion_notoficacion' ,
        'fecha_registro' ,
        'idestatus'
     ];

        public function tipo(){
            return $this->belongsTo(\App\Models\TTipoNotificacion::class,'idtipo','id');
        }
        public function representante(){
            return $this->belongsTo(\App\Models\TPersona::class,'idPersona','idPersona');

        }
}

