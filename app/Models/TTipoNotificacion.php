<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TTipoNotificacion extends Model
{
    //
      protected $table='t_tipo_notificaciones';
      public $incrementing=true;
      public $timestamps=false;
     
      
       protected $fillable=[
        'idOperador' ,
        'id' ,
        'titulo' ,
        'idestatus'
       ];

       public function notificaciones(){
              return $this->hasMany(TNotificacion::class,'idtipo');
         
       }

}
