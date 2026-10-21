<?php

namespace App\Models;

use App\Helpers\ColombiaHelper;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearGlobalCache();
        });

        static::deleted(function () {
            static::clearGlobalCache();
        });
    }

    /**
     * Limpia la caché global asociada a la configuración y assets de empresa.
     */
    public static function clearGlobalCache(): void
    {
        Cache::forget('configuracion_global');
        Cache::forget('empresa_global_data');
        Cache::forget('empresa_logo_base64');
    }

    /**
     * Retorna la configuración global de la empresa cacheada permanentemente
     * para eliminar consultas redundantes a la base de datos (F-03 / F-45).
     */
    public static function getGlobal(): self
    {
        return Cache::rememberForever('configuracion_global', function () {
            return static::firstOrCreate(
                ['id' => 1],
                [
                    'nombre' => 'Tecni Systemas',
                    'nit' => '900.000.000-1',
                    'telefono' => '300 000 0000',
                    'direccion' => 'Calle Principal # 1-1',
                    'correo' => 'contacto@tecnisystemas.com',
                    'pie_pagina_factura' => 'Gracias por su preferencia.',
                    'formato_factura' => 'estandar',
                ]
            );
        });
    }

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
