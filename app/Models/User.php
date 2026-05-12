<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, HasFactory;

    // Campos que permitimos guardar masivamente
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean', 
        ];
    }

    // Método helper para verificar si es admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Método helper para verificar si es tecnico
    public function isTecnico()
    {
        return $this->role === 'tecnico';
    }

    // Método helper para verificar si es invitado
    public function isInvitado()
    {
        return $this->role === 'invitado';
    }
}
