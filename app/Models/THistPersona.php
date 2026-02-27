<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\modelos;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class THistPersona
 * 
 * @property string $idOperador
 * @property string $idfabricante
 * @property string $idPersona
 * @property string $idempresa_Grupo
 * @property string $idsupervisor
 * @property string $idcadenas
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $cod_tipo_persona
 * @property string $idespecialidad
 * @property string $idactividad_negocio
 * @property string $documento_identidad
 * @property string $idgrupo_persona
 * @property string $idclase_persona
 * @property string $idranking
 * @property string $idrol
 * @property string $idtitulo
 * @property string $nombre_persona
 * @property string $apellido_persona
 * @property string $nombre_completo_razon_social
 * @property string $sexo_genero_persona
 * @property \Carbon\Carbon $fecha_nacimiento_registro
 * @property string $telefono_persona
 * @property string $movil_persona
 * @property string $email
 * @property string $idciclos
 * @property string $idhorarios
 * @property string $idfrecuencia
 * @property string $iddias_visita
 * @property string $direccion_domicilio
 * @property string $idestado
 * @property string $idciudad
 * @property string $urbanizacion
 * @property string $zona_postal
 * @property string $banco_persona
 * @property string $cuenta_principal_persona
 * @property string $banco_persona_internacional
 * @property string $aba
 * @property string $switf
 * @property string $username
 * @property string $password
 * @property string $confirm_password
 * @property string $url_foto
 * @property string $url_logo
 * @property string $coordenadas_l
 * @property string $coordenadas_a
 * @property string $terminos_condiciones
 * @property string $persona_contacto
 * @property string $telefono_contacto
 * @property string $email_contacto
 * @property string $idestatus
 *
 * @package App\modelos
 */
class THistPersona extends Eloquent
{
	protected $primaryKey = 'idPersona';
	public $incrementing = false;
	public $timestamps = false;

	protected $dates = [
		'fecha_nacimiento_registro'
	];

	protected $hidden = [
		'password',
		'confirm_password'
	];

	protected $fillable = [
		'idOperador',
		'idfabricante',
		'idempresa_Grupo',
		'idsupervisor',
		'idcadenas',
		'idpais',
		'ididioma',
		'idmoneda',
		'cod_tipo_persona',
		'idespecialidad',
		'idactividad_negocio',
		'documento_identidad',
		'idgrupo_persona',
		'idclase_persona',
		'idranking',
		'idrol',
		'idtitulo',
		'nombre_persona',
		'apellido_persona',
		'nombre_completo_razon_social',
		'sexo_genero_persona',
		'fecha_nacimiento_registro',
		'telefono_persona',
		'movil_persona',
		'email',
		'idciclos',
		'idhorarios',
		'idfrecuencia',
		'iddias_visita',
		'direccion_domicilio',
		'idestado',
		'idciudad',
		'urbanizacion',
		'zona_postal',
		'banco_persona',
		'cuenta_principal_persona',
		'banco_persona_internacional',
		'aba',
		'switf',
		'username',
		'password',
		'confirm_password',
		'url_foto',
		'url_logo',
		'coordenadas_l',
		'coordenadas_a',
		'terminos_condiciones',
		'persona_contacto',
		'telefono_contacto',
		'email_contacto',
		'idestatus'
	];
}
