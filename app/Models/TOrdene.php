<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TOrdene extends Model
{
	//use DML;
	
    protected $primaryKey = 'idorden';
	public $incrementing = true;
	public $timestamps = false;
	
    protected $casts = [
        'impuesto' => 'float',
        'comision' => 'float',
        'costoTotal' => 'float',
        'descuento' => 'float',
        'fechaOrden' => 'datetime',
        'fecha_envio_orden' => 'datetime',
        'fecha_entrega' => 'datetime',
    ];

	protected $guarded = [
		'idorden'
	];

	protected $fillable = [
		'idorden',
		'idOperador',
		'idFabricante',
		'idPersona',
		'idpasadopor',
		'idMayorista',
		'idPersona_solicitante',
		'idpais',
		'ididioma',
		'idmoneda',
		'tipo_operacion',
		'impuesto',
		'comision',
		'costoTotal',
		'descuento',
		'fechaOrden',
		'coordenadas_l', 
		'coordenadas_a',
		'fecha_envio_orden',
		'registro_entrega',
		'fecha_entrega',
		'firma_signature',
		'comentario_entrega',
		'idtipo_incidentes',
		'idfactura',
		'idestatus',
		'TotalUnidades'
	];

	 
        // RELACIÓN CON CLIENTE (USANDO idPersona_solicitante)
    public function cliente()
    {
        return $this->belongsTo(TPersona::class, 'idPersona_solicitante', 'idPersona');
    }

    // RELACIÓN CON RFV (USANDO idpasadopor)
    public function rfv()
    {
        return $this->belongsTo(TPersona::class, 'idpasadopor', 'idPersona');
    }

    public function estatus()
    {
        return $this->belongsTo(TEstatusOrdene::class, 'idestatus', 'idestatus');
    }
public function productos()
{
    return $this->belongsToMany(
        TProducto::class,
        't_item_ordenes',
        'idorden',
        'idproducto'
    )->withPivot([
        'idOperador',
        'idFabricante',
        'nombreproducto',
        'idfactura',
        'idMayorista',
        'cantidad_facturada',
        'cantidad_solicitada',
        'cantidad_conciliada',
        'cantidad_faltante',
        'item_price',
        'item_total',
        'idunidades',
        'item_descuento'
    ]);
}   
    // RELACIÓN SINGULAR (BelongsTo - para el mayorista PRINCIPAL)
    public function mayorista()
    {
        return $this->belongsTo(TPersona::class, 'idMayorista', 'idPersona');
    }

    // RELACIÓN PLURAL (BelongsToMany - para mayoristas secundarios)
    public function Mayoristas()
    {
        return $this->belongsToMany(
            TPersona::class,
            't_orden_mayorista',
            'idorden',
            'idPersona'
        )->withPivot([
            'nombre_mayorista',
            'item_descuento',
            'orden_total',
            'idOperador',
            'idFabricante',
            'idpais',
            'idCliente',
            'ididioma',
            'idmoneda',
            'impuesto',
            'orden_total',
            'idestatus'
        ]);
    }

	public function Factura(){
        return $this->hasOne('App\Models\TFactura', 'idfactura', 'idfactura');
    }

    public function scopeOperadorFabricante($builder,$o,$f)
    {
    	return $builder->where('idOperador',$o)->where('idFabricante',$f);
    }
	/* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
