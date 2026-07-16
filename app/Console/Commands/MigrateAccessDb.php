<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateAccessDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra la información de Base1.mdb al sistema actual (Clientes, Proveedores, Stock)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbPath = base_path('Base1.mdb');
        
        if (!file_exists($dbPath)) {
            $this->error("No se encontró la base de datos de Access en la ruta: $dbPath");
            return;
        }

        $this->info('Conectando a la base de datos de Access...');
        
        $connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$dbPath";
        $conn = odbc_connect($connStr, '', '');

        if (!$conn) {
            $this->error('Fallo al conectar con ODBC. Asegúrate de tener instalado el motor de base de datos de Microsoft Access.');
            return;
        }

        $this->info('Conexión exitosa. Iniciando migración de datos...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->migrarProveedores($conn);
        $this->migrarClientes($conn);
        $this->migrarStock($conn);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        odbc_close($conn);
        
        $this->info('¡Migración completada con éxito!');
    }

    private function migrarProveedores($conn)
    {
        $this->info('--- Migrando Proveedores ---');
        $query = "SELECT * FROM Proveedores";
        $result = odbc_exec($conn, $query);
        
        $count = 0;
        $insertData = [];

        while ($row = odbc_fetch_array($result)) {
            $nombre = trim($row['Nombre'] ?: $row['Razon_Social']);
            if (empty($nombre)) continue; // Evitar proveedores vacíos
            
            $identificacion = trim($row['CUIT']);
            if (empty($identificacion) || $identificacion == '0.0' || $identificacion == '0') {
                $identificacion = 'SD-' . $row['Id']; // SD = Sin Documento
            }

            $telefono = trim($row['Tel1']);
            if (empty($telefono)) $telefono = trim($row['Cel']);
            if (empty($telefono)) $telefono = 'No registrado';

            $insertData[] = [
                'id' => $row['Id'], // Mantenemos el ID para las relaciones
                'nombre_razon_social' => mb_substr($nombre, 0, 255),
                'identificacion' => mb_substr($identificacion, 0, 255),
                'direccion' => mb_substr(trim($row['Domicilio']) ?: 'No registrada', 0, 255),
                'telefono' => mb_substr($telefono, 0, 255),
                'email' => mb_substr(trim($row['Email']) ?: null, 0, 255),
                'ciudad' => 'No registrada',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        // Insertar en lotes
        $chunks = array_chunk($insertData, 100);
        foreach ($chunks as $chunk) {
            DB::table('proveedores')->insertOrIgnore($chunk);
        }

        $this->info("Proveedores migrados: $count");
    }

    private function migrarClientes($conn)
    {
        $this->info('--- Migrando Clientes ---');
        $query = "SELECT * FROM Clientes";
        $result = odbc_exec($conn, $query);
        
        $count = 0;
        $insertData = [];

        while ($row = odbc_fetch_array($result)) {
            $nombre = trim($row['Nombre'] ?: $row['Razon_Social']);
            if (empty($nombre)) continue; // Evitar vacíos
            
            // Usar CUIT o DNI
            $identificacion = trim($row['CUIT']);
            if (empty($identificacion) || $identificacion == '0.0' || $identificacion == '0') {
                $identificacion = trim($row['DNI']);
            }
            if (empty($identificacion) || $identificacion == '0.0' || $identificacion == '0') {
                $identificacion = 'SD-' . $row['Id']; 
            }

            $telefono = trim($row['Tel1']);
            if (empty($telefono)) $telefono = trim($row['Cel']);
            if (empty($telefono)) $telefono = 'No registrado';

            $insertData[] = [
                'id' => $row['Id'], 
                'nombre' => mb_substr($nombre, 0, 255),
                'identificacion' => mb_substr($identificacion, 0, 255),
                'direccion' => mb_substr(trim($row['Domicilio']) ?: 'No registrada', 0, 255),
                'movil' => mb_substr($telefono, 0, 255),
                'email' => mb_substr(trim($row['Email']) ?: null, 0, 255),
                'ciudad' => 'No registrada',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        $chunks = array_chunk($insertData, 100);
        foreach ($chunks as $chunk) {
            DB::table('clientes')->insertOrIgnore($chunk);
        }

        $this->info("Clientes migrados: $count");
    }

    private function migrarStock($conn)
    {
        $this->info('--- Migrando Productos (Stock) ---');
        $query = "SELECT * FROM Stock";
        $result = odbc_exec($conn, $query);
        
        $count = 0;
        $insertData = [];

        while ($row = odbc_fetch_array($result)) {
            $descripcion = trim($row['Descripcion']);
            if (empty($descripcion)) continue;

            $codigo = trim($row['Codigo']);
            if (empty($codigo)) {
                $codigo = 'PROD-' . $row['Id'];
            }

            // Validar ID del proveedor para evitar foreign key errors
            $proveedor_id = $row['IdProveedor'];
            if (!$proveedor_id || !DB::table('proveedores')->where('id', $proveedor_id)->exists()) {
                $proveedor_id = null; // Si el proveedor no existe, lo dejamos nulo
            }

            // Calcular utilidad en base al precio de venta y compra
            $costo = floatval($row['Costo1']);
            $venta = floatval($row['Lista1']);
            $utilidad = 30; // por defecto 30%
            if ($costo > 0 && $venta > $costo) {
                $utilidad = (($venta - $costo) / $costo) * 100;
            }

            $insertData[] = [
                'id' => $row['Id'],
                'codigo' => mb_substr($codigo, 0, 255),
                'producto' => mb_substr($descripcion, 0, 255),
                'cantidad' => floatval($row['Cantidad']),
                'precio_compra' => $costo,
                'precio_venta' => $venta,
                'utilidad' => min(max($utilidad, 0), 999), // Limitar a rango válido
                'proveedor_id' => $proveedor_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        $chunks = array_chunk($insertData, 100);
        foreach ($chunks as $chunk) {
            DB::table('stocks')->insertOrIgnore($chunk);
        }

        $this->info("Productos migrados: $count");
    }
}
