<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\Auditable;

    protected $table = 'proveedores';

    protected $fillable = [
        'tipo_entidad',
        'identificacion',
        'nombre_razon_social',
        'telefono',
        'email',
        'direccion',
        'notas',
        'active',
    ];

    protected $casts = ['active' => 'boolean'];

    /** Artículos de stock que fueron suministrados por este proveedor */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /** Facturas de compra asociadas a este proveedor (polimórfico) */
    public function facturas()
    {
        return $this->morphMany(Factura::class, 'facturable');
    }

    /** Etiqueta legible del tipo de entidad */
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo_entidad === 'empresa' ? '🏢 Empresa' : '👤 Persona';
    }
}
