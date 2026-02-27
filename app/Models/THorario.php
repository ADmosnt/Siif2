<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class THorario extends Model
{
   protected $primaryKey = 'idhorarios';
	public $timestamps = false;

	protected $hidden = ['idOperador','idFabricante','idhorarios',
        'idpais','idestatus'];
	protected $fillable = [
		'idOperador','idFabricante','idhorarios',
		'idpais',
		'descripcion_horarios',
		'idestatus'
	];
}	
