<?php
$dbPath = realpath('Base1.mdb');
$connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$dbPath";
$conn = odbc_connect($connStr, '', '');

if ($conn) {
    $tables = ['Clientes', 'Proveedores', 'Stock'];
    foreach ($tables as $table) {
        echo "\n=== $table ===\n";
        $result = odbc_exec($conn, "SELECT TOP 1 * FROM $table");
        if ($result) {
            $row = odbc_fetch_array($result);
            if ($row) {
                print_r(array_keys($row));
                print_r($row);
            } else {
                echo "Tabla vacía.\n";
            }
        } else {
            echo "Error consultando $table\n";
        }
    }
    odbc_close($conn);
} else {
    echo "Fallo de conexión ODBC.";
}
