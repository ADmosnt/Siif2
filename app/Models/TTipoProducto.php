<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Models\Scopes\OperadorFabricante;

/**
 * Class TTipoProducto
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $cod_tipo_producto
 * @property string $descripcion_tipo_producto
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTipoProducto extends Eloquent
{
	protected $table = 't_tipo_productos';
	protected $primaryKey = 'idtipo_producto';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'descripcion_tipo_producto',
		'idestatus'
	];

	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
