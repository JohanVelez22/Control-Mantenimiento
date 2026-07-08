<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory, \App\Traits\Auditable;

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
            'femenino'  => '♀ Femenino',
            default     => '⊘ Indefinido',
        };
    }

    /** Label del tipo de cliente */
    public function getTipoClienteLabelAttribute(): string
    {
        return $this->tipo_cliente === 'tecnico' ? '🔧 Técnico' : '👤 Cliente';
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
}
