<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TOperadore
 * 
 * @property string $idOperador
 * @property string $idPersona
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $documento_identidad
 * @property string $nombre_completo_razon_social
 * @property string $telefono_persona
 * @property string $movil_persona
 * @property string $email
 * @property string $direccion_domicilio
 * @property string $idestado
 * @property string $idciudad
 * @property string $idpais
 * @property string $url_foto
 * @property string $url_logo
 * @property string $coordenadas_l
 * @property string $coordenadas_a
 * @property string $ipOperador
 * @property \Carbon\Carbon $fecha_inicio
 * @property \Carbon\Carbon $fecha_fin
 * @property string $idestatus
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TOperadore extends Eloquent
{   //use DML;
	protected $table='t_operadores';
	protected $primaryKey = 'id';
	public $incrementing = true;
	public $timestamps = false;

	protected $dates = [
		'fecha_inicio',
		'fecha_fin'
	];

	protected $fillable = [
		'id',
		'nombre_completo_razon_social' ,
		'alias' ,
		'documento_identidad', 
		'email_contacto',
		'persona_contacto' ,
		'telefono_contacto' ,
		'fecha_inicio_operacion' ,
		'fecha_expiracion_operacion'  ,
		'ref_contrato'  ,
		'estatus' ,
		'direccion_domicilio', 
		'idpais' ,
		'idestado' ,
		'idciudad' ,
		'url_foto' ,
		'url_logo'
	];

	protected $request=[
		'fecha_inicio_operacion'=>'inicio',
		'email_contacto'=>'email',
		'telefono_contacto'=>'telefono',
		'documento_identidad'=>'identidad',
		'direccion_domicilio'=>'Direccion',
		'persona_contacto'=>'pcontacto',
		'fecha_expiracion_operacion'=>'expiracion',
		'ref_contrato'=>'contrato'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class,'idOperador');
	}
	public function ciclos(){
		return $this->hasMany(\App\Models\TCiclo::class, 'idOperador');
	}
	public function ranks(){
		return $this->hasMany(\App\Models\TRankingCliente::class, 'idOperador');
	}
}
