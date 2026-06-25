<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Factura extends Model
{
    use SoftDeletes, \App\Traits\Auditable;

    protected $fillable = [
        'numero_factura',
        'tipo_movimiento',
        'estado',
        'facturable_id',
        'facturable_type',
        'total_documento',
        'total_pagado',
        'observaciones',
        'fecha',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha'           => 'date',
            'total_documento' => 'decimal:2',
            'total_pagado'    => 'decimal:2',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────

    /** Cliente O Proveedor asociado (polimórfico) */
    public function facturable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Ítems de la factura */
    public function items(): HasMany
    {
        return $this->hasMany(FacturaItem::class);
    }

    /** Usuario que registró */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Computed Attributes ──────────────────────────────────────

    /** Saldo calculado en PHP (complementa la columna stored) */
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, (float) $this->total_documento - (float) $this->total_pagado);
    }

    public function getEstaAnuladaAttribute(): bool
    {
        return $this->estado === 'anulada';
    }

    public function getTieneSaldoAttribute(): bool
    {
        return $this->saldo_pendiente > 0;
    }

    // ─── Helpers estáticos ────────────────────────────────────────

    /** Genera el siguiente número de factura correlativo */
    public static function siguienteNumero(string $prefijo = 'F'): string
    {
        $ultimo = static::withTrashed()->where('numero_factura', 'like', $prefijo . '%')->latest('id')->value('numero_factura');
        $num    = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;
        return $prefijo . $num;
    }
}
