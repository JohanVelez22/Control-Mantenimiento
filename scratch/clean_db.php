<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Guardar usuarios actuales
$existingUsers = DB::table('users')->get()->toArray();

$systemTables = ['users', 'migrations', 'password_reset_tokens', 'failed_jobs', 'sessions', 'cache', 'cache_locks'];

Schema::disableForeignKeyConstraints();

$allTables = Schema::getTableListing();

foreach ($allTables as $fullTableName) {
    // Extraer solo el nombre de la tabla (después del último punto si contiene el nombre de la base de datos)
    $tableName = str_contains($fullTableName, '.') ? last(explode('.', $fullTableName)) : $fullTableName;

    if (!in_array($tableName, $systemTables)) {
        DB::table($tableName)->truncate();
        echo "✅ Tabla '{$tableName}' vaciada." . PHP_EOL;
    }
}

Schema::enableForeignKeyConstraints();

// Si por alguna razón users se vació, re-insertamos o llamamos al seeder
if (DB::table('users')->count() === 0) {
    if (!empty($existingUsers)) {
        foreach ($existingUsers as $user) {
            DB::table('users')->insert((array)$user);
        }
    } else {
        Artisan::call('db:seed', ['--class' => 'AdminUserSeeder', '--force' => true]);
    }
}

echo "----------------------------" . PHP_EOL;
echo "--- USUARIOS ACTIVOS DE PRUEBA ---" . PHP_EOL;
foreach (DB::table('users')->get() as $u) {
    echo "ID: {$u->id} | {$u->name} | Correo: {$u->email} | Rol: {$u->role}" . PHP_EOL;
}
echo "----------------------------" . PHP_EOL;
echo "🎉 ¡Base de datos totalmente vacía y limpia! Solo conservas los usuarios para loguearte." . PHP_EOL;
