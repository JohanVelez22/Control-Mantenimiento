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

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'item_id');
    }
}
