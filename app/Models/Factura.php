<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Factura extends Model
{
    use SoftDeletes, \App\Traits\Auditable;

    protected $fillable = [
        'numero_factura',
        'tipo_movimiento',
        'estado',
        'facturable_id',
        'facturable_type',
        'total_documento',
        'total_pagado',
        'observaciones',
        'fecha',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha'           => 'date',
            'total_documento' => 'decimal:2',
            'total_pagado'    => 'decimal:2',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────

    /** Cliente O Proveedor asociado (polimórfico) */
    public function facturable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Ítems de la factura */
    public function items(): HasMany
    {
        return $this->hasMany(FacturaItem::class);
    }

    /** Usuario que registró */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Computed Attributes ──────────────────────────────────────

    /** Saldo pendiente (lo que aún se debe). Columna generada, nunca negativo. */
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, (float) ($this->attributes['saldo_pendiente'] ?? 0));
    }

    /** Saldo a favor (lo que se pagó de más). Columna generada. */
    public function getSaldoAFavorAttribute(): float
    {
        return max(0, (float) ($this->attributes['saldo_a_favor'] ?? 0));
    }

    public function getEstaAnuladaAttribute(): bool
    {
        return $this->estado === 'anulada';
    }

    public function getTieneSaldoAttribute(): bool
    {
        return $this->saldo_pendiente > 0;
    }

    /**
     * Sincroniza y recalcula el total pagado a partir de los movimientos de caja activos
     * asociados a la factura, actualizando automáticamente el estado y los saldos.
     */
    public function recalcularPagos(): void
    {
        $directMovIds = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('descripcion', 'like', "%#{$this->numero_factura}%")
            ->pluck('id');

        $pagosCaja = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where(function ($q) use ($directMovIds) {
                if ($directMovIds->isNotEmpty()) {
                    $q->whereIn('id', $directMovIds)
                      ->orWhereIn('parent_id', $directMovIds);
                }
                $q->orWhere('descripcion', 'like', "%#{$this->numero_factura}%");
            })
            ->sum('monto');

        $this->total_pagado = (float) $pagosCaja;

        if ($this->estado !== 'anulada') {
            $saldo = max(0, (float) $this->total_documento - $pagosCaja);
            $this->estado = $saldo > 0.01 ? 'pendiente_pago' : 'emitida';

            // Limpiar etiqueta "⚠️ SALDO PENDIENTE" antigua de observaciones y actualizar si aún hay saldo
            $lineas = array_filter(explode("\n", $this->observaciones ?? ''), fn($l) => !str_contains($l, 'SALDO PENDIENTE:'));
            $obsLimpia = trim(implode("\n", $lineas));

            if ($saldo > 0.01) {
                $obsLimpia .= ($obsLimpia ? "\n" : "") . "⚠️ SALDO PENDIENTE: $" . number_format($saldo, 0, ',', '.');
            }
            $this->observaciones = $obsLimpia ?: null;
        }

        $this->save();
    }

    // ─── Helpers estáticos ────────────────────────────────────────

    /** Genera el siguiente número de factura correlativo (atómico con lockForUpdate) */
    public static function siguienteNumero(string $prefijo = 'F'): string
    {
        return app(\App\Services\OrdenService::class)
            ->siguiente($prefijo, static::class, 'numero_factura');
    }
}
