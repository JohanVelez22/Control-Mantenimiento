<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Borra todos los datos transaccionales (mantenimientos, caja, facturas) dejando intactos los clientes, proveedores y stock para restaurar un ambiente de pruebas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('⚠️ ¿Estás seguro que deseas borrar todas las transacciones de prueba? Esto vaciará Equipos, Mantenimientos, Caja y Facturas.')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info('Iniciando limpieza de base de datos...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToTruncate = [
            'equipos',
            'mantenimientos',
            'mantenimiento_stock',
            'electronicas',
            'electronica_stock',
            'abonos',
            'cierre_cajas',
            'movimiento_cajas',
            'facturas',
            'factura_items'
        ];

        foreach ($tablesToTruncate as $table) {
            DB::table($table)->truncate();
            $this->line(" - Tabla '{$table}' vaciada.");
        }

        // Restablecer el stock a su cantidad inicial migrada
        // Podríamos ejecutar el comando migrate:legacy de forma automatizada
        // Pero es mejor dejar que el usuario lo corra si quiere resetear stock y clientes

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('');
        $this->info('✅ ¡Limpieza de transacciones finalizada con éxito!');
        $this->info('Para restaurar Clientes, Proveedores y Stock a su estado original, corre:');
        $this->line('php artisan migrate:legacy');
    }
}
