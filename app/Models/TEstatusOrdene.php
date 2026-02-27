<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\OperadorFabricante;

class TEstatusOrdene extends Model
{
    protected $primaryKey = 'idestatus';
    public $timestamps = false;

    protected $hidden = ['idOperador','idFabricante'];

    protected $fillable = ['idOperador','idFabricante','idestatus','descripcion'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OperadorFabricante);
    }
}