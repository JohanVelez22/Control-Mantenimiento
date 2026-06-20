<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre', 'marca', 'modelo', 'serie',
        'observacion', 'user_id', 'cliente_id', 'active'
    ];

    protected $casts = ['active' => 'boolean'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Nueva relación: Un equipo fue registrado por un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
