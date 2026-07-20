<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory, \App\Traits\Auditable;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'codigo',
        'cliente_id',
        'fecha',
        'validez_dias',
        'total',
        'estado',
        'anulado',
        'notas',
        'user_id'
    ];

    protected function casts(): array
    {
        return [
            'anulado' => 'boolean',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('anulado', false);
    }
}