<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $fillable = ['mantenimiento_id', 'monto', 'fecha', 'tipo_pago', 'descripcion', 'user_id'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
