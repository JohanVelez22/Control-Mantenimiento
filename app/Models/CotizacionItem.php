<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id',
        'tipo',
        'item_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            if (empty($item->subtotal) || (float)$item->subtotal <= 0) {
                $item->subtotal = (int)($item->cantidad ?? 1) * (float)($item->precio_unitario ?? 0);
            }
        });
    }

    public function getSubtotalAttribute($value): float
    {
        if ($value !== null && (float)$value > 0) {
            return (float)$value;
        }
        return (float)((int)($this->cantidad ?? 1) * (float)($this->precio_unitario ?? 0));
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'item_id');
    }
}
