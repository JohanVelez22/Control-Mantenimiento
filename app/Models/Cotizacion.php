<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory, \App\Traits\Auditable;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'codigo',
        'cliente_id',
        'proveedor_id',
        'fecha',
        'validez_dias',
        'total',
        'estado',
        'anulado',
        'notas',
        'user_id'
    ];

    protected function casts(): array
    {
        return [
            'anulado' => 'boolean',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function getDestinatarioAttribute()
    {
        return $this->cliente ?? $this->proveedor;
    }

    public function getDestinatarioTipoAttribute(): string
    {
        return $this->proveedor_id ? 'proveedor' : 'cliente';
    }

    public function getDestinatarioNombreAttribute(): string
    {
        if ($this->cliente) {
            return $this->cliente->nombre;
        }
        if ($this->proveedor) {
            return $this->proveedor->nombre_razon_social;
        }
        return 'N/A';
    }

    public function getDestinatarioIdentificacionAttribute(): string
    {
        return $this->cliente?->identificacion ?? $this->proveedor?->identificacion ?? '-';
    }

    public function getDestinatarioTelefonoAttribute(): string
    {
        return $this->cliente?->movil ?? $this->cliente?->telefono ?? $this->proveedor?->telefono ?? '';
    }

    public function getDestinatarioLabelAttribute(): string
    {
        if ($this->proveedor) {
            return '🏢 ' . $this->proveedor->nombre_razon_social;
        }
        if ($this->cliente) {
            return '👤 ' . $this->cliente->nombre;
        }
        return 'N/A';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('anulado', false);
    }
}