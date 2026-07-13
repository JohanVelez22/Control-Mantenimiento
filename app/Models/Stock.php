<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = [
        'codigo',
        'producto',
        'categoria',
        'subcategoria',
        'cantidad',
        'proveedor',       // Campo texto legado (aún existe)
        'proveedor_id',    // FK formal
        'precio_compra',
        'utilidad',
        'precio_venta',
        'precio_tecnico',
        'active',
    ];

    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Stock $stock): void {
            // Auto-calcular precio de venta si no se provee
            if (empty($stock->precio_venta) || $stock->precio_venta == 0) {
                $stock->precio_venta = $stock->precio_compra
                    + ($stock->precio_compra * ($stock->utilidad / 100));
            }

            // Auto-calcular precio técnico si no se provee
            if (empty($stock->precio_tecnico) || $stock->precio_tecnico == 0) {
                $stock->precio_tecnico = $stock->precio_compra
                    + ($stock->precio_compra * (($stock->utilidad / 2) / 100));
            }
        });
    }

    // ─── Relaciones ───────────────────────────────────────────────

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function mantenimientos(): BelongsToMany
    {
        return $this->belongsToMany(Mantenimiento::class)
                    ->withPivot('cantidad', 'precio_unitario')
                    ->withTimestamps();
    }

    public function facturaItems(): HasMany
    {
        return $this->hasMany(FacturaItem::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Verifica que haya stock suficiente */
    public function tieneDisponible(int $cantidad): bool
    {
        return $this->cantidad >= $cantidad;
    }

    /** Incrementa el stock de forma atómica (delega en StockService) */
    public function incrementarStock(int $cantidad): void
    {
        app(\App\Services\StockService::class)->entrada($this, $cantidad);
    }

    /** Decrementa el stock de forma atómica (lanza excepción si no hay suficiente) */
    public function decrementarStock(int $cantidad): void
    {
        app(\App\Services\StockService::class)->salida($this, $cantidad);
    }

    /** Scope: solo artículos activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
