<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Models\Scopes\OperadorFabricante;

/**
 * Class TCoberturaRv
 *
 * @property int $cod_cobertura_rv
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idRFV
 * @property \Carbon\Carbon $fechaRegistro
 * @property \Carbon\Carbon $fechaOperacion
 * @property int $cant_esperada
 * @property int $cant_realizada
 * @property int $cant_ordenes_realizadas
 * @property int $cant_ordenes_parciales
 * @property float $porc_reporte_RFV
 * @property float $porc_pedidos_RFV
 * @property float $porc_despachados
 * @property float $porce_cobertura
 * @property string $numero_ciclo
 * @property string $MesRegistro
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TCoberturaRv extends Eloquent{

    protected $table = 't_cobertura_rfv';
    protected $primaryKey = 'cod_cobertura_rfv';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'cod_cobertura_rv'          => 'int',
        'cant_esperada'             => 'int',
        'cant_realizada'            => 'int',
        'cant_ordenes_realizadas'   => 'int',
        'cant_ordenes_parciales'    => 'int',
        'fechaRegistro'             =>'datetime',
        'fechaOperacion'            =>'datetime',
        'TotalUnidades'             =>'int',
        'porc_reporte_RFV'          => 'float',
        'porc_pedidos_RFV'          => 'float',
        'porc_despachados'          => 'float',
        'porce_cobertura'           => 'float'
    ];

    protected $fillable = [
        'cod_cobertura_rfv',
        'idOperador',
        'idFabricante',
        'idRFV',
        'idsupervisor',
        'fechaRegistro',
        'idzona',
        'idruta',
        'cant_esperada',
        'cant_realizada',
        'productoEsperado',
        'productoFacturado',
        'cant_ordenes_realizadas',
        'cant_ordenes_parciales',
        'porc_reporte_RFV',
        'porc_pedidos_RFV',
        'porc_despachados',
        'porce_cobertura',
        'monto_facturado',
        'monto_esperado',
        'numero_ciclo',
        'MesRegistro',
        'idestatus'
    ];

    // El nombre de la función DEBE coincidir con el usado en `with()`.
    public function representante()
    {
        return $this->belongsTo(TPersona::class, 'idRFV', 'idPersona');
    }

    public function supervisor()
    {
        return $this->belongsTo(TPersona::class, 'idsupervisor', 'idPersona');
    }
    
    public function zona()
    {
        return $this->belongsTo(TZona::class, 'idzona', 'idzona');
    }

    public function ruta()
    {
        return $this->belongsTo(TBrickRuta::class, 'idruta', 'idbrick');
    }

}

