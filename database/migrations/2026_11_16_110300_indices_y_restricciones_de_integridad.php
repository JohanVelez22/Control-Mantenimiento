<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * D-13 / D-14 / D-15: índices y restricciones de integridad.
 *
 * - Índices nuevos para el historial (por usuario y por acción, ordenados por fecha) y
 *   para el saldo inicial de caja en efectivo.
 * - Se quitan dos índices redundantes (los cubre otro índice que empieza igual).
 * - Restricciones CHECK (solo MySQL; SQLite no permite agregarlas a una tabla existente):
 *   montos no negativos, abono de exactamente una orden, equipo de exactamente un dueño,
 *   existencias no negativas y anulado coherente con el estado en caja. Antes de crear
 *   cada una se cuentan las filas que la violarían: si hay alguna, esa restricción no se
 *   crea, se anota en el log y `app:verificar-esquema` la reporta como faltante.
 */
return new class extends Migration
{
    private const INDICES = [
        ['eventos', ['user_id', 'created_at'], 'idx_eventos_user_created'],
        ['eventos', ['accion', 'created_at'], 'idx_eventos_accion_created'],
        ['movimiento_cajas', ['tipo_pago', 'estado', 'anulado', 'fecha'], 'idx_movcaja_pago_estado_fecha'],
    ];

    private const REDUNDANTES = [
        ['electronicas', 'idx_elec_fecha_entrada', ['fecha_entrada']],
        ['eventos', 'eventos_modelo_tipo_modelo_id_index', ['modelo_tipo', 'modelo_id']],
    ];

    /** nombre => [tabla, condición que deben cumplir todas las filas] */
    public const CHECKS = [
        'chk_mov_monto' => ['movimiento_cajas', 'monto >= 0'],
        'chk_mov_estado' => ['movimiento_cajas', "(anulado = 1) = (estado = 'anulado')"],
        'chk_abono_monto' => ['abonos', 'monto > 0'],
        'chk_abono_orden' => ['abonos', '(mantenimiento_id IS NULL) <> (electronica_id IS NULL)'],
        'chk_equipo_duenio' => ['equipos', '(cliente_id IS NULL) <> (proveedor_id IS NULL)'],
        'chk_stock_cantidad' => ['stocks', 'cantidad >= 0'],
        'chk_fact_montos' => ['facturas', 'total_documento >= 0 AND total_pagado >= 0'],
        'chk_mant_costo' => ['mantenimientos', 'costo >= 0'],
        'chk_elec_costo' => ['electronicas', 'costo >= 0'],
    ];

    public function up(): void
    {
        foreach (self::INDICES as [$tabla, $columnas, $nombre]) {
            if (! $this->hayIndice($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->index($columnas, $nombre));
            }
        }

        foreach (self::REDUNDANTES as [$tabla, $nombre]) {
            if ($this->hayIndice($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropIndex($nombre));
            }
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::CHECKS as $nombre => [$tabla, $condicion]) {
            if ($this->hayCheck($nombre)) {
                continue;
            }
            $violan = (int) DB::table($tabla)->whereRaw("NOT ({$condicion})")->count();
            if ($violan > 0) {
                Log::warning("Migración de integridad: {$violan} fila(s) de {$tabla} no cumplen {$nombre} ({$condicion}); la restricción no se creó.");

                continue;
            }
            DB::statement("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$nombre}` CHECK ({$condicion})");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (self::CHECKS as $nombre => [$tabla]) {
                if ($this->hayCheck($nombre)) {
                    DB::statement("ALTER TABLE `{$tabla}` DROP CHECK `{$nombre}`");
                }
            }
        }

        foreach (self::REDUNDANTES as [$tabla, $nombre, $columnas]) {
            if (! $this->hayIndice($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->index($columnas, $nombre));
            }
        }

        foreach (self::INDICES as [$tabla, , $nombre]) {
            if ($this->hayIndice($tabla, $nombre)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropIndex($nombre));
            }
        }
    }

    private function hayIndice(string $tabla, string $nombre): bool
    {
        return collect(Schema::getIndexes($tabla))->contains(fn ($i) => strcasecmp($i['name'], $nombre) === 0);
    }

    private function hayCheck(string $nombre): bool
    {
        return DB::table('information_schema.check_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('constraint_name', $nombre)
            ->exists();
    }
};
