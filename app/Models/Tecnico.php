<?php

namespace App\Models;

use App\Helpers\ColombiaHelper;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use Auditable, HasFactory;

    // Permitimos la asignación masiva de estos campos
    protected $fillable = [
        'nombre',
        'identificacion',
        'especialidad',
        'movil',
        'email',
        'direccion',
        'photo',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

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

    // Relación: Un técnico puede tener muchos mantenimientos (Lo usaremos en la siguiente fase)

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
