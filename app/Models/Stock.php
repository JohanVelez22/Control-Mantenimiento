<?php

namespace App\Models;

use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'codigo',
        'producto',
        'categoria',
        'subcategoria',
        'cantidad',
        'proveedor_id',    // FK formal
        'precio_compra',
        'utilidad',
        'precio_venta',
        'precio_tecnico',
        'active',
        'photo',
    ];

    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Stock $stock): void {
            $precioCompra = (float) ($stock->precio_compra ?? 0);
            $utilidad = (float) ($stock->utilidad ?? 0);

            // Auto-calcular precio de venta si no se provee
            if ($precioCompra > 0 && (empty($stock->precio_venta) || (float) $stock->precio_venta == 0.0)) {
                $stock->precio_venta = round($precioCompra * (1 + ($utilidad / 100)), 2);
            }

            // Auto-calcular precio técnico si no se provee
            if ($precioCompra > 0 && (empty($stock->precio_tecnico) || (float) $stock->precio_tecnico == 0.0)) {
                $stock->precio_tecnico = round($precioCompra * (1 + (($utilidad / 2) / 100)), 2);
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

    public function bajas(): HasMany
    {
        return $this->hasMany(BajaStock::class)->orderBy('created_at', 'desc');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Total de unidades dadas de baja por daño o merma */
    public function getTotalUnidadesBajaAttribute(): int
    {
        return (int) $this->bajas()->sum('cantidad');
    }

    /** Total en pesos perdido por mermas y artículos defectuosos */
    public function getTotalPerdidaBajasAttribute(): float
    {
        return (float) $this->bajas()->sum('costo_total_perdida');
    }

    /** Verifica que haya stock suficiente */
    public function tieneDisponible(int $cantidad): bool
    {
        return $this->cantidad >= $cantidad;
    }

    /** Incrementa el stock de forma atómica (delega en StockService) */
    public function incrementarStock(int $cantidad): void
    {
        app(StockService::class)->entrada($this, $cantidad);
    }

    /** Decrementa el stock de forma atómica (lanza excepción si no hay suficiente) */
    public function decrementarStock(int $cantidad): void
    {
        app(StockService::class)->salida($this, $cantidad);
    }

    /** Scope: solo artículos activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
