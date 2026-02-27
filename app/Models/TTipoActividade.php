<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Scopes\OperadorFabricante;

class TTipoActividade extends Model
{
    protected $table = 't_tipo_actividades';
    protected $primaryKey = 'idtipo_actividades';
	public $timestamps = false;

	protected $hidden = ['idOperador','idestatus','idFabricante'];

	protected  $guarded = ['idtipo_actividades'];

	protected $fillable = [
		'idFabricante',
	    'idtipo_actividades',
		'idOperador',
		'descripcion_tipo_actividades',
		'idestatus',
	];

	public function scopeByFabricante($query, $fabricanteId)
    {
        return $query->where('idFabricante', $fabricanteId);
    }

    // Relación con fabricante
    public function fabricante()
    {
        return $this->belongsTo('App\Models\TPersona', 'idFabricante', 'idFabricante');
    }

 protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}