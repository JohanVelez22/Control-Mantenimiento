<?php
$dbPath = realpath('Base1.mdb');
$connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$dbPath";
$conn = odbc_connect($connStr, '', '');

if ($conn) {
    echo "Conexión exitosa a ODBC.\n";
    $result = odbc_tables($conn);
    while (odbc_fetch_row($result)) {
        if(odbc_result($result, "TABLE_TYPE") == "TABLE") {
            echo odbc_result($result, "TABLE_NAME") . "\n";
        }
    }
    odbc_close($conn);
} else {
    echo "Fallo de conexión ODBC.";
}
