<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TPaise extends Model
{
	protected $table='t_paises';
    protected $primaryKey = 'idpais';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idpais',
		'abreviatura',
		'nombrePais',
		'codigo',
		'idestatus'
	];
	
	public static $validators=[
		'id'=>'required|numeric|unique:t_paises,idpais',
		'abreviatura'=>'required|string|max:3',
		'descripcion'=>'required|unique:t_paises,nombrePais',
		'codigo'=>'required|unique:t_paises,codigo|regex:^[0-9]{4}$^',
	
	];


	public function t_estados()
	{
		return $this->hasMany(TEstado::class, 'idpais', 'idpais');
	}
}
