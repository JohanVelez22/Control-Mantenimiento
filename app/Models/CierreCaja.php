<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use Auditable;

    protected $fillable = [
        'fecha', 'total_ingresos', 'total_egresos',
        'efectivo', 'efectivo_real_contado', 'diferencia',
        'consignacion', 'saldo_final',
        'num_movimientos', 'bloqueado', 'observaciones', 'motivo_diferencia', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'bloqueado' => 'boolean',
            'efectivo_real_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    /**
     * Estado de la conciliación: 'cuadrado', 'faltante', 'sobrante', o 'sin_conciliar'
     */
    public function getEstadoDiferenciaAttribute(): string
    {
        if ($this->efectivo_real_contado === null) {
            return 'sin_conciliar';
        }
        $dif = (float) $this->diferencia;
        if (abs($dif) < 0.01) {
            return 'cuadrado';
        }
        return $dif > 0 ? 'sobrante' : 'faltante';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
