<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TEstado extends Model
{
    //
	protected $table = 't_estados';
    public $incrementing = false;
	public $timestamps = false;
	protected $primaryKey='idestado';

	protected $fillable = [
		'idpais',
		'ididioma',
		'nombreCiudad',
		'idestatus'
	];

	public function t_paise()
	{
		return $this->belongsTo(TPaise::class, 'idpais', 'idpais');
	}

	public function t_ciudades()
	{
		return $this->hasMany(TCiudade::class, 'idestado', 'idestado');
	}
}
