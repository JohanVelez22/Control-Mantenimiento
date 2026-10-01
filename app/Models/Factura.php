<?php

namespace App\Models;

use App\Services\OrdenService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use \App\Traits\Auditable, SoftDeletes;

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
            'fecha' => 'date',
            'total_documento' => 'decimal:2',
            'total_pagado' => 'decimal:2',
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

    /** Saldo pendiente (lo que aún se debe). Columna generada en BD con fallback en memoria */
    public function getSaldoPendienteAttribute(): float
    {
        if (isset($this->attributes['saldo_pendiente'])) {
            return max(0, (float) $this->attributes['saldo_pendiente']);
        }

        return max(0, (float) ($this->total_documento ?? 0) - (float) ($this->total_pagado ?? 0));
    }

    /** Saldo a favor (lo que se pagó de más). Columna generada en BD con fallback en memoria */
    public function getSaldoAFavorAttribute(): float
    {
        if (isset($this->attributes['saldo_a_favor'])) {
            return max(0, (float) $this->attributes['saldo_a_favor']);
        }

        return max(0, (float) ($this->total_pagado ?? 0) - (float) ($this->total_documento ?? 0));
    }

    public function getEstaAnuladaAttribute(): bool
    {
        return $this->estado === 'anulada';
    }

    public function getTieneSaldoAttribute(): bool
    {
        return $this->saldo_pendiente > 0;
    }

    /** Costo total de adquisición de los productos en la factura */
    public function getCostoTotalAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return (float) $item->cantidad * (float) ($item->stock->precio_compra ?? 0);
        });
    }

    /** Utilidad o pérdida de la factura (aplica para ventas) */
    public function getUtilidadAttribute(): float
    {
        if ($this->tipo_movimiento !== 'venta') {
            return 0;
        }

        return (float) $this->total_documento - $this->costo_total;
    }

    /**
     * Sincroniza y recalcula el total pagado a partir de los movimientos de caja activos
     * asociados a la factura, actualizando automáticamente el estado y los saldos.
     */
    public const float EPSILON = 0.01;

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'factura_id');
    }

    /**
     * Recalcula el total pagado sumando los movimientos de caja asociados.
     * Actualiza el estado según el saldo pendiente.
     */
    public function recalcularPagos(): void
    {
        $expectedTipo = $this->tipo_movimiento === 'venta' ? 'ingreso' : 'egreso';

        $directMovIds = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('tipo_movimiento', $expectedTipo)
            ->where(function ($q) {
                $q->where('factura_id', $this->id)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('factura_id')
                            ->where('descripcion', 'like', "%#{$this->numero_factura}%");
                    });
            })
            ->pluck('id');

        $pagosCaja = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('tipo_movimiento', $expectedTipo)
            ->where(function ($q) use ($directMovIds) {
                if ($directMovIds->isNotEmpty()) {
                    $q->whereIn('id', $directMovIds)
                        ->orWhereIn('parent_id', $directMovIds);
                }
                $q->orWhere('factura_id', $this->id);
            })
            ->sum('monto');

        $this->total_pagado = $this->estado === 'anulada' ? 0.0 : (float) $pagosCaja;

        if ($this->estado !== 'anulada') {
            $saldo = max(0, (float) $this->total_documento - $pagosCaja);
            $this->estado = $saldo > self::EPSILON ? 'pendiente_pago' : 'emitida';

            // Limpiar etiqueta "⚠️ SALDO PENDIENTE" antigua y duplicados repetitivos de anulación/reactivación
            $lineas = array_filter(explode("\n", $this->observaciones ?? ''), fn ($l) => ! str_contains($l, 'SALDO PENDIENTE:'));
            $tagLines = [];
            $contentLines = [];
            foreach ($lineas as $l) {
                $trimmed = trim($l);
                if (preg_match('/^\[(ANULADA|REACTIVADA) el .* por .*\]$/u', $trimmed)) {
                    $tagLines[] = $trimmed;
                } else {
                    $contentLines[] = $l;
                }
            }
            if (! empty($tagLines)) {
                $contentLines[] = end($tagLines);
            }
            $obsLimpia = trim(implode("\n", $contentLines));

            if ($saldo > self::EPSILON) {
                $obsLimpia .= ($obsLimpia ? "\n" : '').'⚠️ SALDO PENDIENTE: $'.number_format($saldo, 0, ',', '.');
            }
            $this->observaciones = $obsLimpia ?: null;
        }

        $this->save();
    }

    // ─── Helpers estáticos ────────────────────────────────────────

    /** Genera el siguiente número de factura correlativo (atómico con lockForUpdate) */
    public static function siguienteNumero(string $prefijo = 'F'): string
    {
        return app(OrdenService::class)
            ->siguiente($prefijo, static::class, 'numero_factura');
    }
}
