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
}
