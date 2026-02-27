<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TPersonaDia extends Model
{
    protected $table = 't_persona_dias';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'idOperador',
        'idFabricante',
        'idPersona',
        'iddias_visita',
    ];

    public function persona()
    {
        return $this->belongsTo(TPersona::class, 'idPersona', 'idPersona');
    }

    public function diaVisita()
    {
        return $this->belongsTo(TDiasVisita::class, 'iddias_visita', 'iddias_visita');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new OperadorFabricante);
    }
}