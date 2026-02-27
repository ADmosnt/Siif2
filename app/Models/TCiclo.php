<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TCiclo extends Model
{
	protected $table='t_ciclos';
	protected $primaryKey = 'id';
	public $incrementing = true;
	//use DML;

	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'descripcion_ciclos',
		'idestatus'
	];

	public static $validators=[
		'descripcion'=>'required|unique:t_ciclos,descripcion_ciclos',
		'id'=>'required|unique:t_ciclos,id'
	];

	public static function boot(){
		parent::boot();
		static::addGlobalScope(new OperadorFabricante);
	}
}
