<?php

namespace App\Models;

use App\Helpers\ColombiaHelper;
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
        'backup_automatico',
        'backup_frecuencia',
        'backup_hora',
        'backup_dia_semana',
        'backup_dia_mes',
        'backup_max_copias',
        'backup_tipo_incluido',
    ];

    public function getNombreAttribute($value)
    {
        return ColombiaHelper::formatTitleCase($value);
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = ColombiaHelper::formatTitleCase($value);
    }

    public function getDireccionAttribute($value)
    {
        return ColombiaHelper::formatTitleCase($value);
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = ColombiaHelper::formatTitleCase($value);
    }
}
