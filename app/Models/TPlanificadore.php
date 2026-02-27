<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TPlanificadore extends Model
{
    protected $primaryKey = 'idAgenda';
	public $incrementing = false;
	public $timestamps = false;

	protected $guarded = [
		'idAgenda'
	];

	protected $hidden = ['idOperador','idreporte'];

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idAgenda',
		'idRFV',
		'idCliente',
		'fecha_agenda',
		'hora',
		'idbrick',
		'observacion_agenda',
		'idSupervisor',
		'idestatus',
		'idreporte',
	];

	    public function cliente(): BelongsTo
    {
        return $this->belongsTo(TPersona::class, 'idCliente', 'idPersona');
    }

	public function Actividad(){
        return $this->belongsTo(TActividadesRepresentante::class,'idreporte','idreporte');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('idestatus', function (Builder $builder) {
            $builder->where('idestatus', 1);
        });
    }

    public function scopeOperadorFabricante($query, $Operador,$Fabricante)
    {
        return $query->where('idOperador', $Operador)->where('idFabricante',$Fabricante);
    }
    
}
