<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TPersona;

class TTmpPlanificadore extends Model
{   
    // --- CONSTANTES PARA ESTATUS ---
    public const ESTATUS_TEMPORAL = 1;
    public const ESTATUS_PERDIDA = 2;
    
    protected $table = 't_tmp_planificadores';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    public $timestamps = false;

    protected $hidden = ['idOperador'];

    protected $fillable = [
        'idOperador',
        'idFabricante',
        'idRFV',
        'idCliente',
        'Fecha',
        'Hora',
        'idSupervisor',
        'idstatus',
        'idCreador',
        'estatus_visita',
    ];


	    public function rfv(): BelongsTo
    {
        return $this->belongsTo(TPersona::class, 'idRFV', 'idPersona');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(TPersona::class, 'idCliente', 'idPersona');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(TPersona::class, 'idSupervisor', 'idPersona');
    }

    public function fabricante(): BelongsTo
    {
        return $this->belongsTo(TPersona::class, 'idFabricante', 'idFabricante');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('idestatus', function (Builder $builder) {
            $builder->where('idstatus', 1);
        });
    }

    public function scopeOperadorFabricante($query, $Operador,$Fabricante)
    {
        return $query->where('idOperador', $Operador)->where('idFabricante',$Fabricante);
    }

}