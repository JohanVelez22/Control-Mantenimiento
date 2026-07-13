<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'modelo_tipo',
        'modelo_id',
        'valores_antiguos',
        'valores_nuevos',
        'ip_direccion',
        'user_agent',
        'descripcion',
    ];

    protected $casts = [
        'valores_antiguos' => 'array',
        'valores_nuevos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modelo()
    {
        return $this->morphTo(__FUNCTION__, 'modelo_tipo', 'modelo_id');
    }
    
    public static function registrar($accion, $modelo = null, $viejos = null, $nuevos = null, $descripcion = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'modelo_tipo' => $modelo ? get_class($modelo) : null,
            'modelo_id' => $modelo ? $modelo->id : null,
            'valores_antiguos' => $viejos,
            'valores_nuevos' => $nuevos,
            'ip_direccion' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'descripcion' => $descripcion,
        ]);
    }
}
