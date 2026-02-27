<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TMoneda
 * 
 * @property string $idOperador
 * @property string $idmoneda
 * @property string $codmoneda
 * @property string $nombreMoneda
 * @property string $moneda_shortcodigo
 * @property string $moneda_simbolo
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TMoneda extends Eloquent
{
	//use DML;
	protected $primaryKey = 'idmoneda';
	public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idFabricante',
		'idmoneda',
		'codmoneda',
		'nombreMoneda',
		'moneda_shortcodigo',
		'moneda_simbolo',
		'idestatus'
	];

	 public static $validators=[
		 'fabricante'=>'required|exists:t_personas,idPersona',
		 'codmoneda'=>'required|max:3|unique:t_monedas,codmoneda',
		// 'nombremoneda'=>'required|unique:t_monedas,nombremoneda',
		 'moneda_simbolo'=>'required'
	 ];
}
