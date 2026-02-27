<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TRole extends Model
{
    protected $table = 't_roles';
	protected $primaryKey = 'idrol';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idrol', 
		'descripcion_rol',  
		'idestatus' 
	];
}
