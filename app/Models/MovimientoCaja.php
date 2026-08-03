<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use \App\Traits\Auditable;

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
        'user_id',
        'abono_id',   // FK opcional al Abono que generó este movimiento
        'parent_id',  // FK opcional al MovimientoCaja padre (para abonos de caja)
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'anulado' => 'boolean',
        ];
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
                ->filter(fn($p) => !$p->anulado && $p->estado === 'activo')
                ->sum('monto');
        }
        return (float) $this->childPayments()
            ->where('anulado', false)
            ->where('estado', 'activo')
            ->sum('monto');
    }

    public function getRefSearchAttribute()
    {
        if (!$this->descripcion) return null;

        if (preg_match('/#([A-Za-z0-9-]+)/', $this->descripcion, $m)) {
            return '#' . $m[1];
        }
        if (preg_match('/Orden\s+#?([A-Za-z0-9-]+)/i', $this->descripcion, $m)) {
            return 'Orden ' . $m[1];
        }
        if (preg_match('/ELC\s+#?([A-Za-z0-9-]+)/i', $this->descripcion, $m)) {
            return 'ELC ' . $m[1];
        }

        return null;
    }

    public function getTotalPagadoAttribute()
    {
        $rootId = $this->parent_id ?: $this->id;
        $refSearch = $this->ref_search;

        if ($rootId || $refSearch) {
            return (float) self::activos()
                ->where(function($q) use ($rootId, $refSearch) {
                    if ($rootId) {
                        $q->where('id', $rootId)
                          ->orWhere('parent_id', $rootId);
                    }
                    if ($refSearch) {
                        $q->orWhere('descripcion', 'like', "%{$refSearch}%");
                    }
                })
                ->sum('monto');
        }

        return (float) $this->monto;
    }

    public function getEffectiveMontoTotalAttribute()
    {
        if ($this->monto_total && $this->monto_total > 0) {
            return (float) $this->monto_total;
        }

        if ($this->parent_id && $this->parent) {
            return $this->parent->effective_monto_total;
        }

        if ($this->descripcion) {
            if (preg_match('/#([A-Za-z0-9-]+)/', $this->descripcion, $m)) {
                $val = Factura::where('numero_factura', $m[1])->value('total_documento');
                if ($val) return (float) $val;
            }
            if (preg_match('/Orden\s+#?([A-Za-z0-9-]+)/i', $this->descripcion, $m)) {
                $val = Mantenimiento::where('id_orden', $m[1])->value('costo');
                if ($val) return (float) $val;
            }
            if (preg_match('/ELC\s+#?([A-Za-z0-9-]+)/i', $this->descripcion, $m)) {
                $val = Electronica::where('id_orden', $m[1])->value('costo');
                if ($val) return (float) $val;
            }
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
}
