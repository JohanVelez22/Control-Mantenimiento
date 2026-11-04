<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpieza de columnas huérfanas e integridad (F-01, F-02)
        if (Schema::hasColumn('proveedores', 'activo')) {
            try {
                Schema::table('proveedores', function (Blueprint $table) {
                    $table->dropIndex('idx_proveedores_activo');
                });
            } catch (Throwable $e) {
                // Índice inexistente, ignorar
            }

            Schema::table('proveedores', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }

        // Eliminar DEFAULT estático congelado en facturas.fecha (F-01)
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE facturas ALTER COLUMN fecha DROP DEFAULT');
            } catch (Throwable $e) {
                // Ignorar si ya fue removido
            }
        }

        // 2. Unicidad en pivotes y catálogos (F-04, F-05, ANEXO A)
        try {
            Schema::table('mantenimiento_stock', function (Blueprint $table) {
                $table->unique(['mantenimiento_id', 'stock_id'], 'uq_mantenimiento_stock');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('electronica_stock', function (Blueprint $table) {
                $table->unique(['electronica_id', 'stock_id'], 'uq_electronica_stock');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('categoria_stocks', function (Blueprint $table) {
                $table->unique(['nombre', 'tipo'], 'uq_categoria_stocks');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('configuraciones', function (Blueprint $table) {
                $table->unique('nombre', 'uq_configuraciones_nombre');
            });
        } catch (Throwable $e) {
        }

        // 3. Eliminar índices redundantes / prefijos de otros índices (F-17)
        $indicesRedundantes = [
            ['tabla' => 'movimiento_cajas', 'indice' => 'idx_movcaja_tipo'],
            ['tabla' => 'abonos',           'indice' => 'idx_abonos_mantenimiento'],
            ['tabla' => 'abonos',           'indice' => 'idx_abonos_electronica'],
            ['tabla' => 'mantenimientos',   'indice' => 'idx_mant_fecha_entrada'],
            ['tabla' => 'cotizaciones',     'indice' => 'cotizaciones_estado_index'],
            ['tabla' => 'cierre_cajas',     'indice' => 'idx_cierrecaja_fecha'],
            ['tabla' => 'facturas',         'indice' => 'idx_facturas_estado_tipo'],
            ['tabla' => 'mantenimientos',   'indice' => 'idx_mant_estado_anulado'],
            ['tabla' => 'electronicas',     'indice' => 'idx_elec_estado_anulado'],
        ];

        foreach ($indicesRedundantes as $item) {
            try {
                Schema::table($item['tabla'], function (Blueprint $table) use ($item) {
                    $table->dropIndex($item['indice']);
                });
            } catch (Throwable $e) {
                // Continuar si el índice ya fue descartado
            }
        }

        // 4. Crear índices de alto rendimiento faltantes (F-16, F-18)
        try {
            Schema::table('eventos', function (Blueprint $table) {
                $table->index('created_at', 'idx_eventos_created_at');
                $table->index(['modelo_tipo', 'modelo_id', 'created_at'], 'idx_eventos_modelo_created');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->index(['fecha', 'estado'], 'idx_cotizaciones_fecha_estado');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('facturas', function (Blueprint $table) {
                $table->index(['estado', 'saldo_pendiente'], 'idx_facturas_estado_saldo');
            });
        } catch (Throwable $e) {
        }

        // 5. Garantizar fila inicial de configuraciones (F-03)
        if (! app()->environment('testing') && Schema::hasTable('configuraciones') && DB::table('configuraciones')->count() === 0) {
            DB::table('configuraciones')->insert([
                'id' => 1,
                'nombre' => 'Tecni Systemas',
                'nit' => '900.000.000-1',
                'telefono' => '300 000 0000',
                'direccion' => 'Calle Principal # 1-1',
                'correo' => 'contacto@tecnisystemas.com',
                'pie_pagina_factura' => 'Gracias por su preferencia.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir nuevos índices
        try {
            Schema::table('facturas', function (Blueprint $table) {
                $table->dropIndex('idx_facturas_estado_saldo');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dropIndex('idx_cotizaciones_fecha_estado');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('eventos', function (Blueprint $table) {
                $table->dropIndex('idx_eventos_created_at');
                $table->dropIndex('idx_eventos_modelo_created');
            });
        } catch (Throwable $e) {
        }

        // Revertir unicidad
        try {
            Schema::table('configuraciones', function (Blueprint $table) {
                $table->dropUnique('uq_configuraciones_nombre');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('categoria_stocks', function (Blueprint $table) {
                $table->dropUnique('uq_categoria_stocks');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('electronica_stock', function (Blueprint $table) {
                $table->dropUnique('uq_electronica_stock');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('mantenimiento_stock', function (Blueprint $table) {
                $table->dropUnique('uq_mantenimiento_stock');
            });
        } catch (Throwable $e) {
        }
    }
};
