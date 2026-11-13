<?php

use App\Exports\SafeValueBinder;

/*
| Se sobrescriben el value binder y, dentro de `exports`, `strict_null_comparison`
| (X-09: con el valor por defecto, un 0 se escribía como celda vacía). El resto de
| opciones conserva los valores por defecto del paquete maatwebsite/excel.
| `exports` se fusiona aquí porque mergeConfigFrom solo fusiona el primer nivel.
*/
$porDefecto = require base_path('vendor/maatwebsite/excel/config/excel.php');

return [
    'exports' => array_replace($porDefecto['exports'], ['strict_null_comparison' => true]),
    'value_binder' => [
        'default' => SafeValueBinder::class,
    ],
];
