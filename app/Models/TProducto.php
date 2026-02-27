<?php

namespace App\Models;
// app/Models/TProducto.php
use App\Models\Scopes\OperadorFabricante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
//use RTR\Traits\DML;

class TProducto extends Model
{
    protected $table = 't_productos';
    protected $primaryKey = 'idproducto';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'fechaRegistro_producto' => 'datetime',
        'fechaExpedicion_producto' => 'datetime',
        'fechaVencimiento_producto' => 'datetime',
        'FechaInicioPublicacion' => 'datetime',
        'FechaFinPublicacion' => 'datetime',
        'Precio_producto' => 'decimal:2',
        'descuento_producto' => 'decimal:2',
        'cantidad_producto_existente' => 'integer',
    ];

    protected $fillable = [
        'idOperador',
        'idfabricante',
        'idproducto',
        'idPersona',
        'idMayorista',
        'idpais',
        'ididioma',
        'idmoneda',
        'idlinea_producto',
        'idtipo_producto',
        'nombre_producto',
        'descripcion_producto',
        'principio_producto',
        'Precio_producto',
        'descuento_producto',
        'idcategorias',
        'presentacion',
        'cantidad_producto_existente',
        'fechaRegistro_producto',
        'fechaExpedicion_producto',
        'fechaVencimiento_producto',
        'dias_publicacion',
        'idpromocion',
        'lote',
        'FechaInicioPublicacion',
        'FechaFinPublicacion',
        'estatus_producto',
        'idunidades'
    ];

    // Relaciones
    public function fabricante()
    {
        return $this->belongsTo(TPersona::class, 'idfabricante', 'idPersona');
    }

    // Accesores para nombres más limpios
    public function getNombreAttribute()
    {
        return $this->nombre_producto;
    }

    public function getExistenciaAttribute()
    {
        return $this->cantidad_producto_existente;
    }

            // Métodos de negocio
    public function reducirInventario(int $cantidad): bool
    {
        if ($this->cantidad_producto_existente < $cantidad) {
            throw new \Exception('Inventario insuficiente');
        }

        $this->cantidad_producto_existente -= $cantidad;
        return $this->save();
    }

    public function aumentarInventario(int $cantidad): bool
    {
        $this->cantidad_producto_existente += $cantidad;
        return $this->save();
    }


    public function scopeList($query){
        return $query->where('idcategorias','!=',"MUES");
	}

	// -- Relaciones Eloquent

	// - Un producto pertenece a un mayorista (qes es una Persona)
	public function mayorista()
	{
		return $this->belongsTo(TPersona::class, 'idMayorista', 'idPersona');
	}

	// - Un producto pertenece a una linea de producto
	public function lineaProducto()
	{
		return $this->belongsTo(TLineaProducto::class, 'idlinea_producto','id');
	}

	// un producto pertenece a un tipo de producto
	public function tipoProducto()
	{
		return $this->belongsTo(TTipoProducto::class, 'idtipo_producto', 'idtipo_producto');
	}

	// un producto pertenece a una categoria
	public function categoria()
	{
		return $this->belongsTo(TCategoria::class,'idcategorias','idcategorias');
	}

	//un producto puede tener un esquema promocional
	public function promocion()
	{
		return $this->belongsTo(TEsquemaPromocionale::class, 'idpromocion', 'idesqprom');
	}

	// un producto tiene una unidad de medida
	public function unidad()
	{
		return $this->belongsTo(TUnidade::class, 'idunidades', 'id');
	}

    
    protected static function boot()
    {
        parent::boot();
        // static::addGlobalScope(new OperadorFabricante);
    }
}
