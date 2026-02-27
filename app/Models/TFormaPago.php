<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\OperadorFabricante;

class TFormaPago extends Model
{
    protected $primaryKey = 'idformaPago';
	public $incrementing = false;
	public $timestamps = false;

	protected $hidden = [
		'idOperador',
		'idFabricante'];

	protected $fillable = [
		'idOperador',
		'idformaPago',
		'descripcion',
		'idestatus'
	];

	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}
