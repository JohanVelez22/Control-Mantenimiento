<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D-19: algunas migraciones antiguas tragan errores (try/catch vacío) y quedan como
 * ejecutadas aunque el índice único no se haya creado. Aquí se comprueba que existan los
 * índices únicos, índices y restricciones de los que depende el código.
 */
final class VerificadorEsquema
{
    /** tabla => índices (por nombre) que deben existir */
    public const INDICES = [
        'mantenimiento_stock' => ['uq_mantenimiento_stock'],
        'electronica_stock' => ['uq_electronica_stock'],
        'categoria_stocks' => ['uq_categoria_stocks'],
        'configuraciones' => ['uq_configuraciones_nombre'],
        'cierre_cajas' => ['cierre_cajas_fecha_unique'],
        'facturas' => ['facturas_numero_factura_unique'],
        'mantenimientos' => ['mantenimientos_id_orden_unique'],
        'electronicas' => ['electronicas_id_orden_unique'],
        'eventos' => ['idx_eventos_created_at'],
    ];

    /** Índices que agrega la migración 2026_11_16_110300 (solo se exigen si ya se ejecutó). */
    public const INDICES_NUEVOS = [
        'eventos' => ['idx_eventos_user_created', 'idx_eventos_accion_created'],
        'movimiento_cajas' => ['idx_movcaja_pago_estado_fecha'],
    ];

    /** @return array<int, string> descripción de cada elemento que falta */
    public static function faltantes(): array
    {
        $faltan = [];
        $exigidos = self::INDICES;
        if (self::migracionEjecutada('2026_11_16_110300_indices_y_restricciones_de_integridad')) {
            $exigidos = array_merge_recursive($exigidos, self::INDICES_NUEVOS);
        }

        foreach ($exigidos as $tabla => $indices) {
            if (! Schema::hasTable($tabla)) {
                $faltan[] = "tabla {$tabla}";

                continue;
            }
            $existentes = collect(Schema::getIndexes($tabla))->pluck('name')->map(fn ($n) => strtolower($n))->all();
            foreach ($indices as $indice) {
                if (! in_array(strtolower($indice), $existentes, true)) {
                    $faltan[] = "índice {$indice} en {$tabla}";
                }
            }
        }

        if (DB::getDriverName() === 'mysql' && self::migracionEjecutada('2026_11_16_110300_indices_y_restricciones_de_integridad')) {
            $checks = DB::table('information_schema.check_constraints')
                ->where('constraint_schema', DB::getDatabaseName())
                ->pluck('constraint_name')->all();
            $esperados = (require database_path('migrations/2026_11_16_110300_indices_y_restricciones_de_integridad.php'))::CHECKS;
            foreach (array_keys($esperados) as $nombre) {
                if (! in_array($nombre, $checks, true)) {
                    $faltan[] = "restricción {$nombre} (revise el log: había datos que no la cumplían)";
                }
            }
        }

        return $faltan;
    }

    private static function migracionEjecutada(string $nombre): bool
    {
        return Schema::hasTable('migrations') && DB::table('migrations')->where('migration', $nombre)->exists();
    }
}
