<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    // Permitimos la asignación masiva de estos campos
    protected $fillable = [
        'nombre',
        'identificacion',
        'movil',
        'email',
        'direccion'
    ];

     // Relación: Un cliente tiene muchos equipos
    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    /** Facturas de venta asociadas a este cliente (polimórfico) */
    public function facturas()
    {
        return $this->morphMany(Factura::class, 'facturable');
    }
}
