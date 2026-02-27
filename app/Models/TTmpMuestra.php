<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpMuestra
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idRFV
 * @property string $nombre_RFV
 * @property string $idCliente
 * @property string $nombre_cliente_Visita
 * @property string $muestra
 * @property string $lote
 * @property float $maximo_unidades
 * @property string $Alta_Baja_Update
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpMuestra extends Eloquent
{
	protected $primaryKey='idRFV';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'maximo_unidades' => 'float'
	];

	protected $fillable = [
		'idfabricante' ,
		'idRFV',
		'nombre_RFV',
		'idCliente',
		'nombre_cliente_Visita',
		'muestra',
		'lote',
		'maximo_unidades',
		'Alta_Baja_Update',
		'idestatus'
	];
}
