<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = ['mantenimiento_id', 'electronica_id', 'monto', 'fecha', 'tipo_pago', 'descripcion', 'user_id'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function electronica()
    {
        return $this->belongsTo(Electronica::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientoCaja()
    {
        return $this->hasOne(MovimientoCaja::class, 'abono_id');
    }

    protected static function booted()
    {
        static::deleting(function ($abono) {
            if ($abono->movimientoCaja) {
                $abono->movimientoCaja->update(['anulado' => true]);
            }
        });
    }
}