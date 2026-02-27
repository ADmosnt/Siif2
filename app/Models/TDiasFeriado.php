<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TDiasFeriado
 * 
 * @property string $idOperador
 * @property string $idpais
 * @property string $iddias_feriados
 * @property \Carbon\Carbon $fecha_dias_Feriados
 * @property string $descripcion_dias_Feriados
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TDiasFeriado extends Eloquent
{
	//use DML;

	protected $primaryKey = 'iddias_feriados';
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_dias_Feriados'
	];

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idpais'  ,
		'iddias_feriados', 
		'fecha_dias_Feriados' ,
		'descripcion_dias_Feriados' ,
		'idestatus' 
	];
	
	public static $validators=[
		
		//'idpais'=>'required|exists:t_paises,idpais',
		'fabricante'=>'required|exists:t_personas,idPersona',
		//'id'=>'required|exists:t_dias_feriados,iddias_feriados',
		'fecha'=>'required|date',
		'descripcion'=>'required'
	];
}
