<?php

namespace App\Database;

use Illuminate\Database\Connectors\SQLiteConnector as BaseSQLiteConnector;
use PDO;

/**
 * Conector SQLite que registra GREATEST(), función usada por las columnas generadas
 * de facturas (saldo_pendiente / saldo_a_favor) y que SQLite no implementa.
 * Se aplica a cada PDO creado, incluidas las reconexiones.
 */
class SqliteConnector extends BaseSQLiteConnector
{
    public function connect(array $config)
    {
        $pdo = parent::connect($config);

        $pdo->sqliteCreateFunction('GREATEST', fn (...$args) => max(...$args), -1, PDO::SQLITE_DETERMINISTIC);

        return $pdo;
    }
}
