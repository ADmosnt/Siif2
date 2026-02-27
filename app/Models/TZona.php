<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

// Relaciones
use App\Models\TEstado;
use App\Models\TCiudade;

class TZona extends Model
{
    protected $table='t_zonas';
	protected $primaryKey='idzona';
	public $incrementing =true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idzona',
		'descripcion_zona',
		'idestatus',
		'idciudad',
		'idestado',
		'idpais'
	];
	  public static $validators=[
		  'fabricante'=>'required|exists:t_personas,idPersona',
		  'id'=>'required|unique:t_zonas,idzona',
		  'descripcion'=>'required|unique:t_zonas,descripcion_zona',
		  'idpais'=>'required|exists:t_paises,idpais',
		  'idestado'=>'required|exists:t_estados,idestado',
		  'idciudad'=>'required|exists:t_ciudades,idciudad',
	  ];

	public function bricks()
	{
		return $this->hasMany('App\Models\TBrickRuta','idzona','idzona');
	}

	// Relacion: Una Zona pertenece a un estado
	public function estado()
	{
		return $this->belongsTo(TEstado::class, 'idestado', 'idestado');
	}

	// Relacion: Una Zona pertenece a una ciudad
	public function ciudad()
	{
		return $this->belongsTo(TCiudade::class, 'idciudad', 'idCiudad');
	}

	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
