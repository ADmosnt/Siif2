<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TTmpRepresentante
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idRFV
 * @property string $idsupervisor
 * @property string $nombres_RFV
 * @property string $apellidos_RFV
 * @property string $linea
 * @property string $telefono
 * @property string $eMail
 * @property string $idzona
 * @property string $Descripcion_zona
 * @property string $direccion
 * @property string $idBrick
 * @property string $descripcion_Brick
 * @property string $Actividades
 * @property string $Alta_Baja_Update
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TTmpRepresentante extends Eloquent
{   protected $table='t_tmp_representantes';
	protected $primaryKey='idRFV';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idfabricante', 
		'idRFV',
		'idsupervisor',
		'nombres_RFV',
		'apellidos_RFV',
		'linea',
		'telefono',
		'eMail',
		'idzona',
		'Descripcion_zona',
		'direccion',
		'idBrick',
		'descripcion_Brick',
		'Actividades',
		'Alta_Baja_Update',
		'idestatus'
	];
}
