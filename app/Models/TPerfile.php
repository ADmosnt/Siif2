<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPerfile extends Model
{
    public $timestamps = false;
	protected $hidden = ['idOperador'];
	protected $guarded = ['id'];
	protected $fillable = [
		'idOperador',
		'descripcion_perfil',
		'alias',
		'tipo',
		'idestatus'
	];

	public function roles()
    {
        return $this->belongsToMany('App\Models\TRole','t_rol_perfil','idperfil','idrol');
    }
}
