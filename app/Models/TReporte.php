<?php

namespace RTR;

use Illuminate\Database\Eloquent\Model;

class TReporte extends Model
{
    //
      protected $table='t_reportes';
      protected $primaryKey='idReporte';
      public $timestamps = true;

      protected $fillable=[
        'idReporte' ,
        'idRFV' ,
        'idCliente' ,
        'fecha' ,
        'idestatus' 
      ];

      public function rfv(){
          return $this->belongsTo('App\Models\TPersona','idPersona','idRFV');
      }
      public function cliente(){
          return $this->belongsTo('App\Models\TPersona','idPersona','idCliente');
      }
     public function Actividades(){
         return $this->hasMany('App\Models\TActividadRepresentante','idReporte','idReporte');
     }
}
