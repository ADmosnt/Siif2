<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TParametro extends Model
{
    //
      protected $primaryKey='idparametro';
      public $timestamps=false;

        public $fillable=[
            'idOperador',
            'idparametro' ,
            'idFabricante',
            'descripcion' ,
            'valor_meta' ,
            'Porcentaje_Desviacion' ,
            'idestatus'
        ];

    public static $validators=[
       
        'id'=>'required|unique:t_parametros,idparametro',
        'fabricante'=>'required|exists:t_personas,idPersona',
        'descripcion'=>'required|exists:t_parametros,descripcion',
        'valor_meta'=>'required|numeric',
        'Porcentaje_Desviacion'=>'required|numeric|between:0,100',
       
    ];
}
