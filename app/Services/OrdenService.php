<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generación centralizada y atómica de números de orden y documentos (ORD-1, ELC-0001, VT-1, etc.).
 *
 * Utiliza la tabla dedicada `secuencias_documento` con bloqueo pesimista a nivel de fila
 * (lockForUpdate) para garantizar correlatividad estricta y eliminar la condición de carrera
 * (race condition P1-6) que ocurría al consultar tablas vacías o calcular números con id DESC.
 */
class OrdenService
{
    private static ?bool $hayTablaSecuencias = null;

    /**
     * Calcula el siguiente número correlativo atómico.
     *
     * @param  string  $prefijo  Prefijo del documento (p.ej. 'ORD-', 'ELC-', 'VT-', 'CP-').
     * @param  class-string<Model>  $modelo  Modelo dueño de la secuencia.
     * @param  string  $columna  Columna que almacena el identificador (por defecto 'id_orden').
     * @param  int|null  $pad  Si se indica, rellena con ceros (p.ej. 4 => '0001').
     * @param  bool  $lock  true = incrementa y bloquea la secuencia atómicamente; false = solo previsualización sin incremento.
     * @return string Número generado (p.ej. 'VT-1', 'ORD-12' o 'ELC-0003').
     */
    public function siguiente(
        string $prefijo,
        string $modelo,
        string $columna = 'id_orden',
        ?int $pad = null,
        bool $lock = true
    ): string {
        // D-17: la existencia de la tabla se consulta una vez por proceso, no en cada documento.
        self::$hayTablaSecuencias ??= Schema::hasTable('secuencias_documento');
        if (! self::$hayTablaSecuencias) {
            return $this->generarLegacy($prefijo, $modelo, $columna, $pad, $lock);
        }

        $operacion = function () use ($prefijo, $modelo, $columna, $pad, $lock) {
            // Asegurar que exista la fila para el prefijo de forma atómica. La vista previa
            // (formulario de creación, petición GET) no escribe en la base (B-15).
            if ($lock) {
                DB::table('secuencias_documento')->insertOrIgnore([
                    'prefijo' => $prefijo,
                    'valor' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($lock) {
                $secuencia = DB::table('secuencias_documento')
                    ->where('prefijo', $prefijo)
                    ->lockForUpdate()
                    ->first();
            } else {
                $secuencia = DB::table('secuencias_documento')
                    ->where('prefijo', $prefijo)
                    ->first();
            }

            $valorActual = (int) ($secuencia?->valor ?? 0);

            // Siempre se sincroniza con el mayor número existente: si entraron documentos por
            // otra vía (seeder, respaldo restaurado, importación) la secuencia quedaba atrás y
            // generaba números repetidos que fallaban por la restricción unique.
            $base = max($valorActual, $this->obtenerMaximoExistente($modelo, $columna, $prefijo));

            $siguienteNumero = $base + 1;

            if ($lock) {
                DB::table('secuencias_documento')
                    ->where('prefijo', $prefijo)
                    ->update([
                        'valor' => $siguienteNumero,
                        'updated_at' => now(),
                    ]);
            }

            $numStr = $pad ? str_pad((string) $siguienteNumero, $pad, '0', STR_PAD_LEFT) : (string) $siguienteNumero;

            return $prefijo.$numStr;
        };

        if ($lock && ! DB::transactionLevel()) {
            return DB::transaction($operacion);
        }

        return $operacion();
    }

    /**
     * Extrae el número correlativo más alto existente en la tabla del modelo.
     */
    protected function obtenerMaximoExistente(string $modelo, string $columna, string $prefijo): int
    {
        if (! class_exists($modelo)) {
            return 0;
        }

        // B-15 / D-17: el máximo se calcula en SQL (antes se traían todos los números) e
        // incluye los registros borrados lógicamente, que siguen ocupando el número en el
        // índice único.
        $query = $modelo::query()->withoutGlobalScopes();
        if ($prefijo === '') {
            return (int) $query->max($columna);
        }

        $tabla = (new $modelo)->getTable();
        $col = $query->getQuery()->getGrammar()->wrap($columna);
        $desde = mb_strlen($prefijo) + 1;
        $expresion = DB::connection($query->getConnection()->getName())->getDriverName() === 'sqlite'
            ? "MAX(CAST(SUBSTR({$col}, {$desde}) AS INTEGER))"
            : "MAX(CAST(SUBSTRING({$col}, {$desde}) AS UNSIGNED))";

        return (int) DB::connection($query->getConnection()->getName())
            ->table($tabla)
            ->where($columna, 'like', $prefijo.'%')
            ->selectRaw("{$expresion} as maximo")
            ->value('maximo');
    }

    /**
     * Fallback en caso de que la tabla de secuencias no esté disponible.
     */
    protected function generarLegacy(
        string $prefijo,
        string $modelo,
        string $columna,
        ?int $pad,
        bool $lock
    ): string {
        $generar = function () use ($prefijo, $modelo, $columna, $pad, $lock) {
            $query = $modelo::query();
            if (! empty($prefijo)) {
                $query->where($columna, 'like', "{$prefijo}%");
            }
            $query->orderByDesc('id');
            if ($lock) {
                $query->lockForUpdate();
            }

            $ultimo = $query->first();
            $numero = $ultimo ? ((int) preg_replace('/[^0-9]/', '', $ultimo->{$columna})) + 1 : 1;
            $numStr = $pad ? str_pad((string) $numero, $pad, '0', STR_PAD_LEFT) : (string) $numero;

            return $prefijo.$numStr;
        };

        return $lock ? DB::transaction($generar) : $generar();
    }
}
