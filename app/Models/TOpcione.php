<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TOpcione
 * 
 * @property string $idOperador
 * @property string $idopciones
 * @property string $descripcion_opciones
 * @property string $idestatus
 *
 * @package App\modelos
 */
class TOpcione extends Eloquent
{
	protected $primaryKey = 'idopciones';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'idOperador',
		'idopciones',
		'descripcion_opciones',
		'idestatus'
	];

	public function perfiles(){
		return $this->belongsToMany('App\Models\TPerfile','t_opciones_perfiles','opciones_perfil','idperfil');
	}
}
