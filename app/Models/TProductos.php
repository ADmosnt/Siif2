<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TOrdenes;

class TProductos extends Model  {


    public $timestamps=false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 't_productos';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['idOperador', 'idfabricante', 'idproducto', 'idPersona', 'idMayorista', 'idpais', 'ididioma', 'idmoneda', 'idlinea_producto', 'idtipo_producto', 'nombre_producto', 'descripcion_producto', 'principio_producto', 'Precio_producto', 'Descuento_producto', 'idcategorias', 'idunidades', 'cantidad_producto_existente', 'fechaRegistro_producto', 'fechaExpedicion_producto', 'fechaVencimiento_producto', 'dias_publicación', 'idpromocion', 'FechaInicioPublicacion', 'FechaFinPublicacion', 'estatus_producto'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];
    
    protected $primaryKey= 'idproducto';
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['fechaRegistro_producto', 'fechaExpedicion_producto', 'fechaVencimiento_producto', 'FechaInicioPublicacion', 'FechaFinPublicacion'];

    public function orden(){
        return $this->belongsToMany('App\Models\TOrdenes','t_item_ordenes','idproducto','idorden');
    }
}