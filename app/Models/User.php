<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

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

    public function getNameAttribute($value)
    {
        return \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = \App\Helpers\ColombiaHelper::formatTitleCase($value);
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
