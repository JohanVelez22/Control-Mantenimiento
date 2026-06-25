<?php

namespace App\Traits;

use App\Models\Evento;
use Illuminate\Support\Arr;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            Evento::registrar('creado', $model, null, clone_model_data($model->getAttributes()), "Creó un nuevo registro en " . class_basename($model));
        });

        static::updated(function ($model) {
            $cambios = $model->getChanges();
            if (count(Arr::except($cambios, ['updated_at'])) > 0) {
                $changedViejos = [];
                $changedNuevos = [];
                foreach ($cambios as $key => $value) {
                    if ($key !== 'updated_at' && $key !== 'password' && $key !== 'remember_token') {
                        $changedViejos[$key] = $model->getOriginal($key);
                        $changedNuevos[$key] = $value;
                    }
                }
                
                if (count($changedNuevos) > 0) {
                    Evento::registrar('actualizado', $model, $changedViejos, $changedNuevos, "Actualizó un registro de " . class_basename($model));
                }
            }
        });

        static::deleted(function ($model) {
            Evento::registrar('eliminado', $model, clone_model_data($model->getOriginal()), null, "Eliminó un registro de " . class_basename($model));
        });
    }
}

function clone_model_data($data) {
    if (isset($data['password'])) unset($data['password']);
    if (isset($data['remember_token'])) unset($data['remember_token']);
    return $data;
}
