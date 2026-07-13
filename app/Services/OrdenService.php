<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Generación centralizada y atómica de números de orden (ORD-1, ELC-0001, ...).
 *
 * Evita la condición de carrera (race condition) que ocurría al calcular
 * "último id_orden + 1" con lecturas fuera de transacción: bajo concurrencia
 * dos requests obtenían el mismo número. Con lockForUpdate() la fila más
 * reciente se bloquea hasta confirmar la transacción, garantizando unicidad.
 */
class OrdenService
{
    /**
     * Calcula el siguiente número de orden.
     *
     * @param string $prefijo    Prefijo del documento (p.ej. 'ORD-', 'ELC-').
     * @param class-string<Model> $modelo Modelo dueño de la secuencia.
     * @param string $columna    Columna que almacena el id (por defecto 'id_orden').
     * @param int|null $pad      Si se indica, rellena con ceros (p.ej. 4 => '0001').
     * @param bool $lock         true = usa lockForUpdate dentro de transacción (escritura); false = preview sin bloqueo.
     * @return string            Número de orden generado (p.ej. 'ORD-12' o 'ELC-0003').
     */
    public function siguiente(
        string $prefijo,
        string $modelo,
        string $columna = 'id_orden',
        ?int $pad = null,
        bool $lock = true
    ): string {
        $generar = function () use ($prefijo, $modelo, $columna, $pad, $lock) {
            $query = $modelo::orderByDesc('id');
            if ($lock) {
                $query->lockForUpdate();
            }

            $ultimo = $query->first();
            $numero = $ultimo ? ((int) preg_replace('/[^0-9]/', '', $ultimo->{$columna})) + 1 : 1;
            $numStr = $pad ? str_pad((string) $numero, $pad, '0', STR_PAD_LEFT) : (string) $numero;

            return $prefijo . $numStr;
        };

        return $lock ? DB::transaction($generar) : $generar();
    }
}