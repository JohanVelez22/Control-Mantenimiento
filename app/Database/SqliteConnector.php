<?php

namespace App\Database;

use Illuminate\Database\Connectors\SQLiteConnector as BaseSQLiteConnector;
use PDO;
use Pdo\Sqlite;

/**
 * Conector SQLite que registra GREATEST(), función usada por las columnas generadas
 * de facturas (saldo_pendiente / saldo_a_favor) y que SQLite no implementa.
 * Se aplica a cada PDO creado, incluidas las reconexiones.
 *
 * Debe registrarse como determinista: SQLite revalida el esquema completo al alterar
 * cualquier tabla y rechaza funciones no deterministas en columnas generadas. En PHP 8.4
 * el alias antiguo `sqliteCreateFunction` no aplica esa marca; se usa `createFunction`
 * de Pdo\Sqlite cuando existe.
 */
class SqliteConnector extends BaseSQLiteConnector
{
    public function connect(array $config)
    {
        $pdo = parent::connect($config);

        $greatest = fn (...$args) => max(...$args);
        if (class_exists(Sqlite::class) && $pdo instanceof Sqlite) {
            $pdo->createFunction('GREATEST', $greatest, -1, Sqlite::DETERMINISTIC);
        } else {
            $pdo->sqliteCreateFunction('GREATEST', $greatest, -1, PDO::SQLITE_DETERMINISTIC);
        }

        return $pdo;
    }
}
