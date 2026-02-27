<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;


class TRankingCliente extends Model
{	
	
    public $incrementing = true;
	public $timestamps = false;

	protected $hidden = ['idOperador', 'idestatus'];

	protected $guarded = ['id'];

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'descripcion_ranking_cliente',
		'idestatus'
	];

	  public static $validators=[
		  'id'=>'required|unique:t_ranking_clientes,id',
		  'fabricante'=>'required|exists:t_personas,idPersona',
		  'descripcion'=>'required|unique:t_ranking_clientes,descripcion_ranking_cliente'
	  ];


	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
