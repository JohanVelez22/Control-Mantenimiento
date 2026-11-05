<?php

use App\Exports\SafeValueBinder;

/*
| Solo se sobrescribe el value binder; el resto de opciones conserva los valores
| por defecto del paquete maatwebsite/excel (mergeConfigFrom).
*/
return [
    'value_binder' => [
        'default' => SafeValueBinder::class,
    ],
];
