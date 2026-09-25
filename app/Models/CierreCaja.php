<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use Auditable;

    protected $fillable = [
        'fecha', 'total_ingresos', 'total_egresos',
        'efectivo', 'consignacion', 'saldo_final',
        'num_movimientos', 'bloqueado', 'observaciones', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'bloqueado' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
