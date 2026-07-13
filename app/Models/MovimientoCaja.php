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

    public function getSaldoPendienteAttribute()
    {
        if ($this->parent_id) {
            return 0; // Un pago hijo no tiene saldo pendiente por sí mismo
        }
        
        if ($this->monto_total && $this->monto_total > $this->monto) {
            $abonosHijos = $this->childPayments()->where('anulado', false)->sum('monto');
            return max(0, $this->monto_total - ($this->monto + $abonosHijos));
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
