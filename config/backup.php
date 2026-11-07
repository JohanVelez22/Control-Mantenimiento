<?php

/*
|--------------------------------------------------------------------------
| Respaldos de base de datos
|--------------------------------------------------------------------------
| Se leen aquí (y no con env() en el código) para que sigan funcionando con
| `php artisan config:cache`, que deja env() devolviendo solo el valor por defecto.
*/
return [
    // Días que se conservan los respaldos antes de rotarlos.
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 15),

    // Carpeta sincronizada (p. ej. Google Drive) donde se copia cada respaldo. Vacío = no copiar.
    'drive_path' => env('GOOGLE_DRIVE_BACKUP_PATH'),

    // Binarios de MySQL. Vacío = búsqueda automática en rutas conocidas de ServBay / Linux.
    'mysqldump_path' => env('MYSQLDUMP_PATH'),
    'mysql_path' => env('MYSQL_PATH'),
];
