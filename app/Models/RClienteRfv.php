<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\OperadorFabricante;

class RClienteRfv extends Model
{
    protected $table = 'r_cliente_rfv';
    protected $primaryKey = ['idOperador', 'idFabricante', 'id_cliente'];
    public $incrementing = false;
    public $timestamps = false;
    
    protected $fillable = [
        'idOperador',
        'idFabricante', 
        'id_cliente',
        'id_RFV',
        'idprofesion',
        'idespecialidad',
        'idsubespecialidad',
    ];
    
    // Relación con el cliente - SIN Global Scope para evitar conflictos
    public function cliente()
    {
        return $this->belongsTo(TPersona::class, 'id_cliente', 'idPersona')
                    ->withoutGlobalScopes();
    }
    
    // Relación con el RFV - SIN Global Scope
    public function rfv()
    {
        return $this->belongsTo(TPersona::class, 'id_RFV', 'idPersona')
                    ->withoutGlobalScopes();
    }
    
    // Scopes para filtrar por diferentes criterios
    public function scopeByRfv($query, $idRfv)
    {
        return $query->where('id_RFV', $idRfv);
    }
    
    public function scopeByFabricante($query, $idFabricante)
    {
        return $query->where('idFabricante', $idFabricante);
    }
    
}