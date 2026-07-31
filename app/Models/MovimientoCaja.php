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

    public function getTotalPagadoAttribute()
    {
        if ($this->parent_id) {
            return $this->parent ? $this->parent->total_pagado : (float) $this->monto;
        }
        return (float) ($this->monto + $this->total_abonos);
    }

    public function getSaldoPendienteAttribute()
    {
        if ($this->parent_id) {
            return $this->parent ? $this->parent->saldo_pendiente : 0;
        }
        
        if ($this->monto_total && $this->monto_total > 0) {
            return max(0, (float) $this->monto_total - $this->total_pagado);
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
