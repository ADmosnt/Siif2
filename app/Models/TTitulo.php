<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TTitulo extends Model
{
    //
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'id',
		'descripcion',
		'idestatus'
	];

	public static $validators=[
		'id'=>'required|unique:t_titulos,id',
		'descripcion'=>'required|unique:t_titulos,descripcion'
	];

	public function getdescripcionAttribute($value)
    {
        return  utf8_encode($value);
    }
}
