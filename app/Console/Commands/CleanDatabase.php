<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las tablas de datos (clientes, proveedores, stocks, facturas, etc.) manteniendo usuarios y configuraciones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('¿Estás seguro de que deseas vaciar las tablas de datos? Se perderá toda la información transaccional y de catálogos.')) {
            $this->info('Operación cancelada.');
            return;
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
            'mantenimiento_respuestos',
            'equipos',
            'facturas',
            'factura_items',
            'factura_pagos',
            'cotizaciones',
            'cotizacion_items',
            'movimientos_caja',
            'conceptos_caja',
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
