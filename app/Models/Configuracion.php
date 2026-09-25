<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use Auditable;

    protected $table = 'configuraciones';

    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'direccion',
        'correo',
        'logo_path',
        'pie_pagina_factura',
        'formato_factura',
    ];
}
