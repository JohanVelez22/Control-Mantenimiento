<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory, \App\Traits\Auditable;
    protected $fillable = [
        'nombre', 'marca', 'modelo', 'serie',
        'observacion', 'user_id', 'cliente_id', 'proveedor_id', 'active',
        'estado', 'motivo_baja', 'observacion_baja', 'fecha_baja', 'baja_user_id'
    ];

    protected $casts = [
        'active' => 'boolean',
        'fecha_baja' => 'datetime',
    ];

    public function getSerieAttribute($value)
    {
        return $value ? strtoupper($value) : $value;
    }

    public function setSerieAttribute($value)
    {
        $this->attributes['serie'] = $value ? strtoupper($value) : $value;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function getPropietarioAttribute()
    {
        return $this->cliente ?? $this->proveedor;
    }

    public function getPropietarioTipoAttribute(): string
    {
        return $this->proveedor_id ? 'proveedor' : 'cliente';
    }

    public function getPropietarioNombreAttribute(): string
    {
        if ($this->cliente) {
            return $this->cliente->nombre;
        }
        if ($this->proveedor) {
            return $this->proveedor->nombre_razon_social;
        }
        return 'N/A';
    }

    public function getPropietarioIdentificacionAttribute(): string
    {
        return $this->cliente?->identificacion ?? $this->proveedor?->identificacion ?? '-';
    }

    public function getPropietarioTelefonoAttribute(): string
    {
        return $this->cliente?->movil ?? $this->cliente?->telefono ?? $this->proveedor?->telefono ?? '';
    }

    public function getPropietarioLabelAttribute(): string
    {
        if ($this->proveedor) {
            return '🏢 Proveedor: ' . $this->proveedor->nombre_razon_social;
        }
        if ($this->cliente) {
            return '👤 Cliente: ' . $this->cliente->nombre;
        }
        return 'N/A';
    }

    // Nueva relación: Un equipo fue registrado por un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Usuario que registró la baja del equipo
    public function bajaUser()
    {
        return $this->belongsTo(User::class, 'baja_user_id');
    }

    public function getEstaDadoDeBajaAttribute(): bool
    {
        return $this->estado === 'dado_de_baja' || !$this->active;
    }

    public function getMotivoBajaLabelAttribute(): string
    {
        return match ($this->motivo_baja) {
            'irreparable'          => 'Daño irreparable / Irrecuperable',
            'desguace_repuestos'   => 'Desguace / Uso para repuestos',
            'chatarrizacion'       => 'Chatarrización / Desecho',
            'siniestro'            => 'Siniestro / Pérdida total',
            'abandonado'           => 'Equipo abandonado por el cliente',
            default                => ucfirst(str_replace('_', ' ', $this->motivo_baja ?? 'Dado de baja')),
        };
    }

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true)->where('estado', '!=', 'dado_de_baja');
    }
}
