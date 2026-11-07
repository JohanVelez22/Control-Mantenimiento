<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ConfirmaBaseDeDatos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabase extends Command
{
    use ConfirmaBaseDeDatos;

    /**
     * El nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'app:clean-data';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Limpia las tablas de datos (clientes, proveedores, stocks, facturas, etc.) manteniendo usuarios y configuraciones.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('Este comando destructivo está estrictamente deshabilitado en entornos de producción.');

            return 1;
        }

        if (! $this->confirmarBaseDeDatos('Vaciar las tablas de datos y catálogos')) {
            return 1;
        }

        $this->info('Desactivando claves foráneas...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'clientes',
            'tecnicos',
            'proveedores',
            'stocks',
            'mantenimientos',
            'electronicas',
            'mantenimiento_stock',
            'electronica_stock',
            'mantenimiento_respuestos',
            'equipos',
            'facturas',
            'factura_items',
            'factura_pagos',
            'cotizaciones',
            'cotizacions',
            'cotizacion_items',
            'movimiento_cajas',
            'movimientos_caja',
            'concepto_cajas',
            'conceptos_caja',
            'abonos',
            'cierre_cajas',
            'cierres_caja',
            'categoria_stocks',
            'eventos',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("Trunkando tabla: $table");
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('Restaurando claves foráneas...');

        $this->info('¡Base de datos limpiada con éxito! Lista para migración limpia.');
    }
}
