<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory, \App\Traits\Auditable;
    protected $fillable = [
        'nombre', 'marca', 'modelo', 'serie',
        'observacion', 'user_id', 'cliente_id', 'proveedor_id', 'active'
    ];

    protected $casts = ['active' => 'boolean'];

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

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
