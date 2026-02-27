<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\Fabricante;

class TClasePersona extends Model
{
    //
    protected $primaryKey = 'id';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'id',
		'idOperador',
		'idFabricante',
		'descripcion',
		'idestatus'
	];

	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/
/*
	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new Fabricante);
    }*/
}
