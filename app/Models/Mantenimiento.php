<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_orden',
        'fecha_entrada',
        'fecha_salida',
        'tipo',
        'reparacion',
        'descripcion',
        'costo',
        'estado',
        'equipo_id',
        'tecnico_id',
        'user_id'
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrada' => 'date',
            'fecha_salida' => 'date',
        ];
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }

    public function stocks()
    {
        return $this->belongsToMany(Stock::class)
                    ->withPivot('cantidad', 'precio_unitario')
                    ->withTimestamps();
    }

    /** Total abonado */
    public function getTotalAbonadoAttribute(): float
    {
        return (float) $this->abonos->sum('monto');
    }

    /** Saldo pendiente */
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, (float) $this->costo - $this->total_abonado);
    }
}
