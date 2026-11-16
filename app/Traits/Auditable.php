<?php

namespace App\Traits;

use App\Models\Evento;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Registra en `eventos` las altas, cambios, anulaciones y borrados del modelo.
 *
 * - ARQ-10: el enmascarado de datos personales está en un solo lugar (MASCARA) y ya no
 *   depende de una función global declarada dentro del archivo del trait.
 * - B-04: la descripción nombra el registro (número de orden, de factura, código o id)
 *   para poder seguir su historial.
 */
trait Auditable
{
    /** Campos que nunca se guardan en claro en el historial. */
    private const MASCARA = ['password', 'remember_token', 'email', 'identificacion', 'telefono', 'movil', 'direccion'];

    /** Campos que no aportan al historial. */
    private const IGNORADOS = ['updated_at', 'password', 'remember_token'];

    public static function bootAuditable()
    {
        static::created(function ($model) {
            // El try/catch evita que un fallo de auditoría derribe la operación principal
            try {
                Evento::registrar('creado', $model, null, self::limpiarParaAuditoria($model->getAttributes()),
                    'Creó '.self::nombreParaAuditoria($model));
            } catch (\Exception $e) {
                Log::warning('Auditable::created — no se pudo registrar evento: '.$e->getMessage());
            }
        });

        static::updated(function ($model) {
            try {
                $cambios = Arr::except($model->getChanges(), self::IGNORADOS);
                if ($cambios === []) {
                    return;
                }

                $viejos = [];
                $nuevos = [];
                foreach ($cambios as $key => $value) {
                    $viejos[$key] = $model->getOriginal($key);
                    $nuevos[$key] = $value;
                }
                $viejos = self::limpiarParaAuditoria($viejos);
                $nuevos = self::limpiarParaAuditoria($nuevos);

                [$accion, $verbo] = match (true) {
                    isset($nuevos['anulado'], $viejos['anulado']) && $nuevos['anulado'] == 1 && $viejos['anulado'] == 0 => ['anulado', 'Anuló'],
                    isset($nuevos['estado']) && $nuevos['estado'] === 'anulada' => ['anulado', 'Anuló'],
                    isset($nuevos['active'], $viejos['active']) && $nuevos['active'] == 0 && $viejos['active'] == 1 => ['anulado', 'Inactivó (anuló)'],
                    default => ['actualizado', 'Actualizó'],
                };

                Evento::registrar($accion, $model, $viejos, $nuevos, $verbo.' '.self::nombreParaAuditoria($model));
            } catch (\Exception $e) {
                Log::warning('Auditable::updated — no se pudo registrar evento: '.$e->getMessage());
            }
        });

        static::deleted(function ($model) {
            try {
                Evento::registrar('eliminado', $model, self::limpiarParaAuditoria($model->getOriginal()), null,
                    'Eliminó '.self::nombreParaAuditoria($model));
            } catch (\Exception $e) {
                Log::warning('Auditable::deleted — no se pudo registrar evento: '.$e->getMessage());
            }
        });
    }

    /** Enmascara datos personales y convierte fechas a texto. */
    private static function limpiarParaAuditoria(array $datos): array
    {
        foreach ($datos as $clave => $valor) {
            if (in_array($clave, self::MASCARA, true)) {
                $datos[$clave] = '*** ENMASCARADO ***';
            } elseif ($valor instanceof \DateTimeInterface) {
                $datos[$clave] = $valor->format('Y-m-d H:i:s');
            }
        }

        return $datos;
    }

    /** "el registro de Mantenimiento ORD-12", "el registro de Factura VT-3", "… #5". */
    private static function nombreParaAuditoria($model): string
    {
        $ref = $model->getAttribute('id_orden')
            ?? $model->getAttribute('numero_factura')
            ?? $model->getAttribute('codigo')
            ?? ($model->getKey() ? '#'.$model->getKey() : null);

        return 'el registro de '.class_basename($model).($ref ? ' '.$ref : '');
    }
}
