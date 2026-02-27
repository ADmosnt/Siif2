<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TEmpresaGrupo extends Model
{
    protected $table = "t_empresa_grupo";
    protected $primaryKey = "id";
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'Descripcion',
        'idOperador'
    ];
}
