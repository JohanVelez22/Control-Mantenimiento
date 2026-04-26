<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Exception;

class SetupDatabase extends Command
{
    // El comando que escribiremos en la terminal
    protected $signature = 'db:setup';

    // Descripción del comando
    protected $description = 'Crea la base de datos automáticamente según el .env y ejecuta las migraciones';

    /**
     * Lógica principal del comando.
     * Detecta el motor de base de datos y prepara el entorno.
     */
    public function handle()
    {
        // Obtiene la configuración del archivo .env
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection === 'sqlite') {
            // Lógica para SQLite: Crea el archivo si no existe
            if (!file_exists($database)) {
                touch($database);
                $this->info("Base de datos SQLite creada en: {$database}");
            }
        } else {
            // Lógica para MySQL o PostgreSQL
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            try {
                // Nos conectamos al servidor SIN especificar la base de datos
                $pdo = new PDO("{$connection}:host={$host};port={$port}", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Ejecutamos la consulta para crear la base de datos si no existe
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $this->info("Base de datos '{$database}' asegurada/creada correctamente en {$connection}.");
            } catch (Exception $e) {
                $this->error("Error al crear la base de datos: " . $e->getMessage());
                return;
            }
        }

        // Una vez creada, ejecutamos las migraciones frescas con seeders
        $this->info('Ejecutando migraciones y cargando datos de prueba (seeders)...');
        $this->call('migrate:fresh', [
            '--seed' => true,
        ]);
        $this->info('¡Configuración de base de datos y carga de datos completada!');
    }
}
