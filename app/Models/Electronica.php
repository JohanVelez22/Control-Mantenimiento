<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Electronica extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_orden',
        'cliente',
        'dispositivo',
        'marca',
        'descripcion_problema',
        'tipo',
        'costo',
        'estado',
        'fecha_entrada',
        'fecha_salida',
        'tecnico_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrada' => 'date',
            'fecha_salida'  => 'date',
        ];
    }

    /** Días transcurridos desde la entrada (o hasta la salida si está terminado) */
    public function getDiasTranscurridosAttribute(): int
    {
        $fin = ($this->estado === 'terminado' && $this->fecha_salida)
            ? $this->fecha_salida
            : Carbon::today();
        return (int) $this->fecha_entrada->diffInDays($fin);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
