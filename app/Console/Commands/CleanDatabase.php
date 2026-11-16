<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ConfirmaBaseDeDatos;
use App\Models\Evento;
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
    protected $signature = 'app:clean-data {--produccion : D-26: permite vaciar los datos de demostración en producción, previo respaldo verificado}';

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
        $enProduccion = app()->environment('production');
        if ($enProduccion && ! $this->option('produccion')) {
            $this->error('Este comando destructivo está deshabilitado en producción. Para retirar los datos de demostración antes de operar use: php artisan app:clean-data --produccion');

            return 1;
        }

        if (! $this->confirmarBaseDeDatos('Vaciar las tablas de datos y catálogos', permitirProduccion: true)) {
            return 1;
        }

        // D-26: en producción, primero un respaldo completo y verificado (vuelta atrás garantizada).
        if ($enProduccion) {
            $this->info('Generando respaldo previo...');
            if ($this->call('app:backup-db', ['--sin-rotacion' => true]) !== 0) {
                $this->error('No se pudo generar el respaldo previo: no se borró nada.');

                return 1;
            }
            if (config('database.default') === 'mysql' && $this->call('app:verificar-respaldo') !== 0) {
                $this->error('El respaldo previo no pasó la verificación: no se borró nada.');

                return 1;
            }
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
            'bajas_stock',
            'secuencias_documento',
        ];
        // El historial de eventos no se borra: queda constancia de la limpieza.

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("Trunkando tabla: $table");
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('Restaurando claves foráneas...');

        Evento::registrar('datos_limpiados', null, null, ['tablas' => $tables],
            'Se vaciaron los datos de operación y catálogos (usuarios, configuración e historial se conservan).');

        $this->info('¡Base de datos limpiada con éxito! Usuarios, configuración e historial se conservaron.');
    }
}
