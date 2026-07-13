<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory, \App\Traits\Auditable;
    protected $fillable = [
        'nombre', 'marca', 'modelo', 'serie',
        'observacion', 'user_id', 'cliente_id', 'active'
    ];

    protected $casts = ['active' => 'boolean'];

    public function getSerieAttribute($value)
    {
        return $value ? strtoupper($value) : $value;
    }

    public function setSerieAttribute($value)
    {
        $this->attributes['serie'] = $value ? strtoupper($value) : $value;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Nueva relación: Un equipo fue registrado por un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
