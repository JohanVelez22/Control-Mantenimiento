<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BajaStock extends Model
{
    use HasFactory, \App\Traits\Auditable;

    protected $table = 'bajas_stock';

    protected $fillable = [
        'stock_id',
        'user_id',
        'cantidad',
        'precio_compra_unitario',
        'costo_total_perdida',
        'motivo',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_compra_unitario' => 'decimal:2',
        'costo_total_perdida' => 'decimal:2',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMotivoLabelAttribute(): string
    {
        return match ($this->motivo) {
            'defectuoso_fabrica' => 'Defectuoso de fábrica (Garantía)',
            'dano_taller'        => 'Daño accidental en taller',
            'obsoleto'           => 'Obsoleto / Deterioro',
            'perdida_merma'      => 'Pérdida / Merma de inventario',
            default              => ucfirst(str_replace('_', ' ', $this->motivo ?? 'Otro')),
        };
    }
}
