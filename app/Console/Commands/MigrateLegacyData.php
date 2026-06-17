<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Stock;

class MigrateLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa datos antiguos de Clientes, Proveedores y Stock desde CSV extraídos de Access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Limpiando tablas (TRUNCATE) para reiniciar los IDs desde 1...');
        
        // Reset IDs (Soporta PostgreSQL y MySQL)
        try {
            DB::statement('TRUNCATE TABLE clientes RESTART IDENTITY CASCADE');
            DB::statement('TRUNCATE TABLE proveedores RESTART IDENTITY CASCADE');
            DB::statement('TRUNCATE TABLE stocks RESTART IDENTITY CASCADE');
        } catch (\Exception $e) {
            // Si es SQLite o MySQL sin PostgreSQL syntax
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('clientes')->truncate();
            DB::table('proveedores')->truncate();
            DB::table('stocks')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Iniciando migración de datos legacy...');

        $clientesPath = storage_path('app/migration/Clientes.csv');
        $proveedoresPath = storage_path('app/migration/Proveedores.csv');
        $stockPath = storage_path('app/migration/Stock.csv');

        // MIGRAR CLIENTES
        if (file_exists($clientesPath)) {
            $this->info('Migrando Clientes...');
            $clientes = $this->readCsv($clientesPath);
            $count = 0;
            foreach ($clientes as $row) {
                // Determine name
                $nombre = !empty($row['Nombre']) ? $row['Nombre'] : (!empty($row['Razon_Social']) ? $row['Razon_Social'] : 'Desconocido');
                
                // Determine ID
                $id = !empty($row['CUIT']) ? $row['CUIT'] : (!empty($row['DNI']) ? $row['DNI'] : 'CL-'.$row['Id']);
                
                // Telefono
                $movil = !empty($row['Cel']) ? $row['Cel'] : (!empty($row['Tel1']) ? $row['Tel1'] : 'N/A');

                Cliente::updateOrCreate(
                    ['identificacion' => $id],
                    [
                        'nombre' => $nombre,
                        'movil' => $movil,
                        'email' => !empty($row['Email']) ? $row['Email'] : null,
                        'direccion' => !empty($row['Domicilio']) ? $row['Domicilio'] : null,
                    ]
                );
                $count++;
            }
            $this->info("Clientes migrados: $count");
        } else {
            $this->warn('Archivo Clientes.csv no encontrado.');
        }

        // MIGRAR PROVEEDORES
        if (file_exists($proveedoresPath)) {
            $this->info('Migrando Proveedores...');
            $proveedores = $this->readCsv($proveedoresPath);
            $count = 0;
            foreach ($proveedores as $row) {
                $nombre = !empty($row['Razon_Social']) ? $row['Razon_Social'] : (!empty($row['Nombre']) ? $row['Nombre'] : 'Desconocido');
                $id = !empty($row['CUIT']) ? $row['CUIT'] : 'PR-'.$row['Id'];
                $telefono = !empty($row['Cel']) ? $row['Cel'] : (!empty($row['Tel1']) ? $row['Tel1'] : null);

                Proveedor::updateOrCreate(
                    ['identificacion' => $id],
                    [
                        'tipo_entidad' => 'empresa',
                        'nombre_razon_social' => $nombre,
                        'telefono' => $telefono,
                        'email' => !empty($row['Email']) ? $row['Email'] : null,
                        'direccion' => !empty($row['Domicilio']) ? $row['Domicilio'] : null,
                        'notas' => !empty($row['Comentarios']) ? $row['Comentarios'] : null,
                    ]
                );
                $count++;
            }
            $this->info("Proveedores migrados: $count");
        } else {
            $this->warn('Archivo Proveedores.csv no encontrado.');
        }

        // MIGRAR STOCK
        if (file_exists($stockPath)) {
            $this->info('Migrando Stock...');
            $stock = $this->readCsv($stockPath);
            $count = 1; // Contador para TS001
            foreach ($stock as $row) {
                $codigo = 'TS' . str_pad($count, 3, '0', STR_PAD_LEFT);
                $precio_venta = is_numeric($row['Lista1']) ? (float)$row['Lista1'] : 0;
                
                Stock::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'producto' => !empty($row['Descripcion']) ? $row['Descripcion'] : 'Producto sin nombre',
                        'cantidad' => is_numeric($row['Cantidad']) ? (int)$row['Cantidad'] : 0,
                        'precio_compra' => is_numeric($row['Costo1']) ? (float)$row['Costo1'] : 0,
                        'precio_venta' => $precio_venta,
                        'utilidad' => 0, // Por defecto
                        'precio_tecnico' => $precio_venta, // Mismo valor que P. Venta solicitado por el usuario
                        'proveedor' => null
                    ]
                );
                $count++;
            }
            $this->info("Stock migrado: " . ($count - 1));
        } else {
            $this->warn('Archivo Stock.csv no encontrado.');
        }

        $this->info('¡Migración finalizada con éxito!');
    }

    private function readCsv($filename)
    {
        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            // Read line by line
            while (($row = fgetcsv($handle, 4096, ',')) !== false) {
                // Fix BOM in header if present
                if (!$header) {
                    $header = $row;
                    $header[0] = 'Id';
                } else {
                    if (count($header) == count($row)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }
}
