<?php

/*
|--------------------------------------------------------------------------
| Contraseñas iniciales de las cuentas sembradas
|--------------------------------------------------------------------------
| O-14: se leen aquí (y no con env() en el código) para que funcionen igual con
| `php artisan config:cache`. Solo las usa AdminUserSeeder al crear una cuenta que no
| existe. En producción deben quedar VACÍAS una vez fijadas las contraseñas reales con
| `php artisan app:restablecer-password` (S-01); `security:check` y `app:preflight` avisan
| si alguna sigue definida.
*/
return [
    'passwords_iniciales' => [
        'admin' => env('ADMIN_DEFAULT_PASSWORD'),
        'tecnico' => env('TECNICO_DEFAULT_PASSWORD'),
        'invitado' => env('INVITADO_DEFAULT_PASSWORD'),
    ],
];
