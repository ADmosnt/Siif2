<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TUnidade extends Model
{
    public $incrementing = true;
	public $timestamps = false;

	protected $fillable = [
        'id',
		'descripcion_unidades',
		'idestatus'
	];
}

