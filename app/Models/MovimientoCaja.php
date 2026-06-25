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
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function getSaldoPendienteAttribute()
    {
        if ($this->monto_total && $this->monto_total > $this->monto) {
            return $this->monto_total - $this->monto;
        }
        return 0;
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
