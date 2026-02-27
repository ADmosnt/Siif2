<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TIdioma
 * 
 * @property string $idOperador
 * @property string $ididioma
 * @property string $descripcion
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TIdioma extends Eloquent
{
	 //use DML;
	protected $primaryKey = 'ididioma';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'ididioma',
		'descripcion',
		'idestatus'
	];

	public static $validators=[
		'id'=>'required|max:3|unique:t_idiomas,ididioma',
		'descripcion'=>'required|unique:t_idiomas,descripcion'
	];
}
