<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TFacturas extends Model  {

    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 't_facturas';

    protected $primaryKey = 'idfactura';

    public $timestamps = false;
    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['idOperador','idFabricante', 'idfactura', 'idordenes', 'idPersona_solicitante', 'idformaPago', 'fechaFactura', 'Fecha_pago_factura', 'DireccionCliente', 'ReferenciaFactura', 'DescripcionFactura', 'Banco', 'NumeroCuentaReferencia', 'Impuesto_iva', 'RetencionLegal', 'Descuento', 'Porc_Descuento', 'MontoNeto_Factura', 'Monto_Factura', 'idMayorista_despacho', 'idestatus'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['idOperador','NumeroCuentaReferencia'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['fechaFactura', 'Fecha_pago_factura'];

    public function orden()
    {
        return $this->belongsTo('App\Models\TOrdenes', 'idorden');
    }
    

}