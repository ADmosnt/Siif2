<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpClienteRepresentante
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idciclos
 * @property string $tipo_Compania
 * @property string $idcadena
 * @property string $idRFV
 * @property string $nombres_RFV
 * @property string $apellidos_RFV
 * @property string $idCliente
 * @property string $nombre_cliente_Visita
 * @property string $idranking
 * @property string $tipo_especialidad
 * @property string $especialidad
 * @property string $linea
 * @property string $idestado
 * @property string $idciudad
 * @property string $idzona
 * @property string $direccion
 * @property string $telefono
 * @property string $idBrick
 * @property string $email_cliente
 * @property string $idhorarios
 * @property string $idfrecuencia
 * @property string $iddias_visita
 * @property string $Alta_Baja_Update
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpClienteRepresentante extends Eloquent
{
	protected $table='t_tmp_cliente_representantes';
	protected $primaryKey='idRFV';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idciclos',
		'tipo_Compania',
		'idcadena',
		'idRFV',
		'nombres_RFV',
		'apellidos_RFV',
		'idCliente',
		'nombre_cliente_Visita',
		'idranking',
		'tipo_especialidad',
		'especialidad',
		'linea',
		'idestado',
		'idciudad',
		'idzona',
		'direccion',
		'telefono',
		'idBrick',
		'email_cliente',
		'idhorarios',
		'idfrecuencia',
		'iddias_visita',
		'Alta_Baja_Update',
		'idestatus'
	];
}
