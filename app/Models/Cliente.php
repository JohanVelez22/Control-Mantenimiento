<?php

namespace App\Models;

use App\Helpers\ColombiaHelper;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'tipo_identificacion',
        'identificacion',
        'genero',
        'tipo_cliente',
        'movil',
        'email',
        'direccion',
        'departamento',
        'municipio',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function getIdentificacionAttribute($value)
    {
        return $value ? strtoupper($value) : $value;
    }

    public function setIdentificacionAttribute($value)
    {
        $this->attributes['identificacion'] = $value ? strtoupper($value) : $value;
    }

    public function getNombresAttribute($value)
    {
        return ColombiaHelper::formatTitleCase($value);
    }

    public function setNombresAttribute($value)
    {
        $this->attributes['nombres'] = ColombiaHelper::formatTitleCase($value);
    }

    public function getApellidosAttribute($value)
    {
        return ColombiaHelper::formatTitleCase($value);
    }

    public function setApellidosAttribute($value)
    {
        $this->attributes['apellidos'] = ColombiaHelper::formatTitleCase($value);
    }

    public function getDireccionAttribute($value)
    {
        return ColombiaHelper::formatTitleCase($value);
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = ColombiaHelper::formatTitleCase($value);
    }

    /**
     * Retorna el nombre completo de forma dinámica.
     */
    public function getNombreAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre;
    }

    /** Label legible del género */
    public function getGeneroLabelAttribute(): string
    {
        return match ($this->genero) {
            'masculino' => '♂ Masculino',
            'femenino' => '♀ Femenino',
            default => '⊘ Indefinido',
        };
    }

    /** Label del tipo de cliente */
    public function getTipoClienteLabelAttribute(): string
    {
        return $this->tipo_cliente === 'tecnico' ? '🛠️ Técnico' : '👤 Cliente';
    }

    /** Abreviatura legible del tipo de identificación (ej. C.C., NIT, Pass, etc.) */
    public function getTipoIdentificacionAbreviadoAttribute(): string
    {
        return ColombiaHelper::abreviaturaTipoIdentificacion($this->tipo_identificacion);
    }

    /** Relación: Un cliente tiene muchos equipos */
    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    /** Facturas de venta asociadas a este cliente (polimórfico) */
    public function facturas()
    {
        return $this->morphMany(Factura::class, 'facturable');
    }

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
