<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use HasFactory;
    // Permitimos la asignación masiva de estos campos
    protected $fillable = [
        'nombre',
        'identificacion',
        'especialidad',
        'movil',
        'email',
        'direccion',
        'photo'
    ];

    // Relación: Un técnico puede tener muchos mantenimientos (Lo usaremos en la siguiente fase)
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }
}
