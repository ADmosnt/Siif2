<?php 

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OperadorFabricante implements Scope
{
    /**
     * Aplica el scope al constructor de consultas de Eloquent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */

    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()){
            $user = auth()->user();
            
            if (isset($user->idOperador) && isset($user->idFabricante)){
                $tableName = $model->getTable();

                $builder->where($tableName . '.idOperador', $user->idOperador)
                        ->where($tableName . '.idFabricante', $user->idFabricante);

            }
        }
    }

}