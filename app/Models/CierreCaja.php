<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    protected $fillable = [
        'fecha', 'total_ingresos', 'total_egresos',
        'efectivo', 'consignacion', 'saldo_final',
        'num_movimientos', 'bloqueado', 'observaciones', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha'    => 'date',
            'bloqueado' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
