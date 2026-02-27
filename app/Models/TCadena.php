<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Models\Scopes\OperadorFabricante;
/**
 * Class TCadena
 * 
 * @property string $idOperador
 * @property string $idcadenas
 * @property string $descripcion
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TCadena extends Eloquent
{  
	 //use DML;
    protected $table='t_cadenas';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'id',
		'idOperador', 
		'idFabricante', 
		'descripcion' ,
		'estatus' 
	];

	 protected $request=[
		 'idFabricante'=>'Fabricante'
	 ];

	 public static $validators=[
		 'fabricante'=>'required|exists:t_personas,idPersona',
		 'descripcion'=>'required|unique:t_cadenas,descripcion'
	 ];

	public static function boot(){
		parent::boot();
		static::addGlobalScope(new OperadorFabricante);
	}
}
