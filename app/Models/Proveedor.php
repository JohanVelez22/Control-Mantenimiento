<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'tipo_entidad',
        'tipo_identificacion',
        'identificacion',
        'nombre_razon_social',
        'telefono',
        'telefono2',
        'contacto_nombre',
        'email',
        'direccion',
        'departamento',
        'municipio',
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

    public function getIdentificacionAttribute($value)
    {
        return $value ? strtoupper($value) : $value;
    }

    public function setIdentificacionAttribute($value)
    {
        $this->attributes['identificacion'] = $value ? strtoupper($value) : $value;
    }

    public function getNombreRazonSocialAttribute($value)
    {
        return \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function setNombreRazonSocialAttribute($value)
    {
        $this->attributes['nombre_razon_social'] = \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function getContactoNombreAttribute($value)
    {
        return \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function setContactoNombreAttribute($value)
    {
        $this->attributes['contacto_nombre'] = \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function getDireccionAttribute($value)
    {
        return \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = \App\Helpers\ColombiaHelper::formatTitleCase($value);
    }

    /** Etiqueta legible del tipo de entidad */

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo_entidad === 'empresa' ? '🏢 Empresa' : '👤 Persona';
    }

    /** Abreviatura legible del tipo de identificación (ej. C.C., NIT, Pass, etc.) */
    public function getTipoIdentificacionAbreviadoAttribute(): string
    {
        return \App\Helpers\ColombiaHelper::abreviaturaTipoIdentificacion($this->tipo_identificacion);
    }

    /** Scope: solo registros activos (no dados de baja lógicamente) */
    public function scopeActivos($query)
    {
        return $query->where('active', true);
    }
}
