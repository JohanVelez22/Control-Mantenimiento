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
                    $accion = 'actualizado';
                    $mensaje = "Actualizó un registro de " . class_basename($model);

                    // Detect annulments (either 'anulado' = 1 or 'estado' = 'anulada')
                    if (isset($changedNuevos['anulado']) && $changedNuevos['anulado'] == 1 && isset($changedViejos['anulado']) && $changedViejos['anulado'] == 0) {
                        $accion = 'anulado';
                        $mensaje = "Anuló un registro de " . class_basename($model);
                    } elseif (isset($changedNuevos['estado']) && $changedNuevos['estado'] === 'anulada') {
                        $accion = 'anulado';
                        $mensaje = "Anuló un registro de " . class_basename($model);
                    } elseif (isset($changedNuevos['active']) && $changedNuevos['active'] == 0 && isset($changedViejos['active']) && $changedViejos['active'] == 1) {
                        $accion = 'anulado';
                        $mensaje = "Inactivó (anuló) un registro de " . class_basename($model);
                    }

                    Evento::registrar($accion, $model, $changedViejos, $changedNuevos, $mensaje);
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
