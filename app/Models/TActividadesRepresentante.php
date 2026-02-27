<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

use App\Models\TPersona;
use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use App\Models\TProducto;

class TActividadesRepresentante extends Model
{
    protected $table = 't_actividades_representante';
	protected $primaryKey='idreporte';
	public $incrementing = true;
	public $timestamps = false;

	protected $casts = [
		'fecha_actividad'=> 'datetime',
		'coordenadas_l'	 => 'decimal:7',
		'coordenadas_a'  => 'decimal:7',
	];

		protected $fillable = [
		'idOperador',
		'idFabricante',
		'idPersona',
		'idRFV',
		'idCliente',
		'idtipo_actividades',
		'Firma_cliente',
		'fecha_actividad',
		'idtipo_incidentes',
		'observaciones_cliente',
		'coordenadas_l',
		'coordenadas_a',
		'idestatus'
	];

	// --- Relaciones ---
	public function representante()
	{
		return $this->belongsTo(TPersona::class, 'idRFV','idPersona');
	}

    public function cliente(){
	   return $this->belongsTo(TPersona::class, 'idCliente','idPersona');
    }

   	public function tipoActividad(){
		return $this->belongsTo(TTipoActividade::class, 'idtipo_actividades' ,'idtipo_actividades' );
   	}

    public function incidente(){
		return $this->belongsTo(TTipoIncidente::class, 'idtipo_incidentes' ,'idtipo_incidentes' );
    }

    public function Muestras(){
	  	return $this->belongsToMany(TProducto::class,'r_actividades_productos','idreporte','idproducto')
					->withPivot('cantidad','idOperador','idFabricante');
    }

	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
