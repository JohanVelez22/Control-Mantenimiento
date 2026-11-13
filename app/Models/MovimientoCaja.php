<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use Auditable;

    protected $fillable = [
        'empresa',
        'persona',
        'fecha',
        'concepto_id',
        'tipo_movimiento',
        'tipo_pago',
        'monto',
        'monto_total',
        'descripcion',
        'estado',
        'anulado',
        'anulado_en_cascada', // anulado al anular la factura vinculada (no individualmente)
        'user_id',
        'abono_id',   // FK opcional al Abono que generó este movimiento
        'parent_id',  // FK opcional al MovimientoCaja padre (para abonos de caja)
        'factura_id', // FK opcional a la Factura comercial vinculada
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'anulado' => 'boolean',
            'anulado_en_cascada' => 'boolean',
        ];
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childPayments()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getTotalAbonosAttribute()
    {
        if ($this->relationLoaded('childPayments')) {
            return (float) $this->childPayments
                ->filter(fn ($p) => ! $p->anulado && $p->estado === 'activo')
                ->sum('monto');
        }

        return (float) $this->childPayments()
            ->where('anulado', false)
            ->where('estado', 'activo')
            ->sum('monto');
    }

    /** Movimiento raíz: el propio, o su padre si es un abono a otro movimiento. */
    public function raiz(): self
    {
        if (! $this->parent_id) {
            return $this;
        }

        return $this->parent ?: (self::find($this->parent_id) ?? $this);
    }

    /** Orden (mantenimiento o electrónica) a la que pertenece el pago, si es un abono de orden. */
    public function ordenDelAbono(): Mantenimiento|Electronica|null
    {
        if (! $this->abono_id) {
            return null;
        }
        $abono = $this->abono;

        return $abono?->mantenimiento ?? $abono?->electronica;
    }

    /**
     * D-05 / M-02: pagos activos del mismo documento, enlazados SOLO por clave: el
     * movimiento raíz y sus abonos, los pagos de la misma factura (`factura_id`) o los
     * de la misma orden (`abono_id`). Antes se buscaba "#VT-1" u "Orden ORD-1" en la
     * descripción con LIKE '%…': lento (N+1 y recorrido completo) y un ingreso manual
     * que mencionara la referencia contaba como pago del documento.
     */
    public function consultaPagosRelacionados(): Builder
    {
        $raiz = $this->raiz();
        $orden = $raiz->ordenDelAbono();

        return self::activos()->where(function ($q) use ($raiz, $orden) {
            $q->where('id', $raiz->id)->orWhere('parent_id', $raiz->id);
            if ($raiz->factura_id) {
                $q->orWhere('factura_id', $raiz->factura_id);
            }
            if ($orden) {
                $q->orWhereIn('abono_id', $orden->abonos()->select('id'));
            }
        });
    }

    public function getTotalPagadoAttribute()
    {
        $raiz = $this->raiz();

        // Caso común del listado (sin factura ni orden): con los abonos ya cargados no
        // hace falta ninguna consulta.
        if (! $raiz->factura_id && ! $raiz->abono_id && $raiz->relationLoaded('childPayments')) {
            $abonos = (float) $raiz->childPayments
                ->filter(fn ($p) => ! $p->anulado && $p->estado === 'activo')
                ->sum('monto');

            return (float) ($raiz->anulado || $raiz->estado !== 'activo' ? 0 : $raiz->monto) + $abonos;
        }

        return (float) $this->consultaPagosRelacionados()->sum('monto');
    }

    /** Total del documento al que corresponde el pago (monto_total, factura u orden). */
    public function getEffectiveMontoTotalAttribute()
    {
        if ($this->monto_total && $this->monto_total > 0) {
            return (float) $this->monto_total;
        }

        $raiz = $this->raiz();
        if ($raiz->monto_total && $raiz->monto_total > 0) {
            return (float) $raiz->monto_total;
        }

        if ($raiz->factura_id) {
            return (float) ($raiz->factura?->total_documento ?? 0);
        }

        if ($orden = $raiz->ordenDelAbono()) {
            return (float) $orden->costo;
        }

        return 0;
    }

    public function getSaldoPendienteAttribute()
    {
        $totalTotal = $this->effective_monto_total;
        if ($totalTotal > 0) {
            return max(0, (float) $totalTotal - (float) $this->total_pagado);
        }

        return 0;
    }

    // ─── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: movimientos activos y no anulados (dinero real de caja).
     * Uso: MovimientoCaja::activos()->where(...)
     */
    public function scopeActivos($query)
    {
        return $query->where('anulado', false)->where('estado', 'activo');
    }

    public function concepto()
    {
        return $this->belongsTo(ConceptoCaja::class, 'concepto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function abono()
    {
        return $this->belongsTo(Abono::class, 'abono_id');
    }
}
