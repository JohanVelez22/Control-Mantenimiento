<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'codigo',
        'producto',
        'cantidad',
        'proveedor',
        'precio_compra',
        'utilidad',
        'precio_venta',
        'precio_tecnico',
    ];

    protected static function booted()
    {
        static::saving(function ($stock) {
            // Si el precio de venta es 0 o no se proporciona, se calcula automáticamente
            if (empty($stock->precio_venta) || $stock->precio_venta == 0) {
                $stock->precio_venta = $stock->precio_compra + ($stock->precio_compra * ($stock->utilidad / 100));
            }

            // Si el precio técnico es 0 o no se proporciona, por defecto será el precio de compra o aplicar un margen menor
            if (empty($stock->precio_tecnico) || $stock->precio_tecnico == 0) {
                // Por defecto, le pondremos un margen técnico menor (mitad de utilidad) o igual al costo.
                // Como regla, si no dicen el margen, sumaremos la mitad del margen al tecnico.
                $stock->precio_tecnico = $stock->precio_compra + ($stock->precio_compra * (($stock->utilidad / 2) / 100));
            }
        });
    }
}
