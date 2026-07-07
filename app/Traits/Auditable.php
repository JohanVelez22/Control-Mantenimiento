<?php

namespace App\Traits;

use App\Models\Evento;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            // El try/catch evita que un fallo de auditoría derribe la operación principal
            try {
                Evento::registrar(
                    'creado',
                    $model,
                    null,
                    clone_model_data($model->getAttributes()),
                    'Creó un nuevo registro en ' . class_basename($model)
                );
            } catch (\Exception $e) {
                Log::warning('Auditable::created — no se pudo registrar evento: ' . $e->getMessage());
            }
        });

        static::updated(function ($model) {
            try {
                $cambios = $model->getChanges();

                if (count(Arr::except($cambios, ['updated_at'])) === 0) {
                    return;
                }

                $changedViejos = [];
                $changedNuevos = [];

                foreach ($cambios as $key => $value) {
                    if (in_array($key, ['updated_at', 'password', 'remember_token'])) {
                        continue;
                    }
                    $changedViejos[$key] = $model->getOriginal($key);
                    $changedNuevos[$key] = $value;
                }

                if (count($changedNuevos) === 0) {
                    return;
                }

                $accion  = 'actualizado';
                $mensaje = 'Actualizó un registro de ' . class_basename($model);

                if (isset($changedNuevos['anulado']) && $changedNuevos['anulado'] == 1
                    && isset($changedViejos['anulado']) && $changedViejos['anulado'] == 0) {
                    $accion  = 'anulado';
                    $mensaje = 'Anuló un registro de ' . class_basename($model);
                } elseif (isset($changedNuevos['estado']) && $changedNuevos['estado'] === 'anulada') {
                    $accion  = 'anulado';
                    $mensaje = 'Anuló un registro de ' . class_basename($model);
                } elseif (isset($changedNuevos['active']) && $changedNuevos['active'] == 0
                          && isset($changedViejos['active']) && $changedViejos['active'] == 1) {
                    $accion  = 'anulado';
                    $mensaje = 'Inactivó (anuló) un registro de ' . class_basename($model);
                }

                Evento::registrar($accion, $model, $changedViejos, $changedNuevos, $mensaje);
            } catch (\Exception $e) {
                Log::warning('Auditable::updated — no se pudo registrar evento: ' . $e->getMessage());
            }
        });

        static::deleted(function ($model) {
            try {
                Evento::registrar(
                    'eliminado',
                    $model,
                    clone_model_data($model->getOriginal()),
                    null,
                    'Eliminó un registro de ' . class_basename($model)
                );
            } catch (\Exception $e) {
                Log::warning('Auditable::deleted — no se pudo registrar evento: ' . $e->getMessage());
            }
        });
    }
}

/**
 * Limpia datos sensibles antes de guardarlos en el log de auditoría.
 * Función global para compatibilidad con el uso existente.
 */
if (! function_exists('clone_model_data')) {
    function clone_model_data($data)
    {
        unset($data['password'], $data['remember_token']);
        return $data;
    }
}
