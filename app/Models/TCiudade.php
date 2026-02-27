<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCiudade extends Model
{
    //
	protected $table = 't_ciudades'; 
    public $incrementing = false;
	public $timestamps = false;
    protected $primaryKey='idCiudad';

	protected $fillable = [
		'nombreCiudad',
		'idestatus',
		'idpais',
		'idestado'
	];

    public function t_estado()
    {
        return $this->belongsTo(TEstado::class, 'idestado', 'idestado');
    }
    
    public function t_paise()
    {
        return $this->belongsTo(TPaise::class, 'idpais', 'idpais');
    }
}
