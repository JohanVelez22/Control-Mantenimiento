<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verificación de solo lectura antes de ejecutar `php artisan migrate` en una base con datos.
 *
 * Algunas migraciones crean restricciones unique; si existen registros duplicados la
 * restricción no se crea. Este comando lista esos duplicados (y otros problemas de datos
 * conocidos) para corregirlos antes de migrar. No modifica nada.
 */
class VerificarMigraciones extends Command
{
    protected $signature = 'app:verificar-migraciones';

    protected $description = 'Revisa (solo lectura) duplicados y datos que impedirían aplicar las migraciones pendientes';

    public function handle(): int
    {
        $problemas = 0;

        $pendientes = collect($this->migracionesPendientes());
        $this->info('Migraciones pendientes: '.$pendientes->count());
        $pendientes->each(fn ($m) => $this->line("  · {$m}"));

        $chequeos = [
            'mantenimiento_stock (mantenimiento_id, stock_id)' => ['mantenimiento_stock', ['mantenimiento_id', 'stock_id']],
            'electronica_stock (electronica_id, stock_id)' => ['electronica_stock', ['electronica_id', 'stock_id']],
            'categoria_stocks (nombre, tipo)' => ['categoria_stocks', ['nombre', 'tipo']],
            'configuraciones (nombre)' => ['configuraciones', ['nombre']],
            'mantenimientos (id_orden)' => ['mantenimientos', ['id_orden']],
            'electronicas (id_orden)' => ['electronicas', ['id_orden']],
            'facturas (numero_factura)' => ['facturas', ['numero_factura']],
        ];

        foreach ($chequeos as $etiqueta => [$tabla, $columnas]) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }
            $duplicados = DB::table($tabla)
                ->select($columnas)
                ->selectRaw('COUNT(*) as repeticiones')
                ->groupBy($columnas)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicados->isEmpty()) {
                $this->info("✅ Sin duplicados en {$etiqueta}");

                continue;
            }

            $problemas += $duplicados->count();
            $this->error("❌ {$duplicados->count()} grupo(s) duplicado(s) en {$etiqueta}:");
            $this->table([...$columnas, 'repeticiones'], $duplicados->map(fn ($d) => (array) $d)->all());
        }

        // Usuario que las pruebas de navegador (Dusk) podían crear en la base de trabajo
        if (Schema::hasTable('users') && DB::table('users')->where('email', 'admin_caja_dusk@test.com')->exists()) {
            $problemas++;
            $this->error('❌ Existe el usuario de pruebas admin_caja_dusk@test.com (contraseña conocida). Elimínelo o desactívelo.');
        }

        $this->newLine();
        if ($problemas === 0) {
            $this->info('Todo listo: puede hacer un respaldo y ejecutar php artisan migrate --force.');

            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$problemas} problema(s). Corríjalos (con respaldo previo) antes de migrar.");

        return self::FAILURE;
    }

    /** @return string[] */
    private function migracionesPendientes(): array
    {
        $ejecutadas = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration')->all() : [];

        return collect(glob(database_path('migrations/*.php')))
            ->map(fn ($archivo) => basename($archivo, '.php'))
            ->reject(fn ($nombre) => in_array($nombre, $ejecutadas, true))
            ->values()
            ->all();
    }
}
