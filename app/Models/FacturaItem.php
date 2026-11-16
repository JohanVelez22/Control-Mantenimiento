<?php

namespace App\Models;

use App\Support\Esquema;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaItem extends Model
{
    use Auditable;

    protected $fillable = [
        'factura_id',
        'stock_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'costo_unitario',
    ];

    /**
     * M-11: cada ítem guarda su costo al momento de registrarse (en una venta, el costo
     * promedio vigente del producto; en una compra, el precio pagado). Así la utilidad de
     * una venta pasada no cambia cuando después cambia el precio de compra.
     */
    protected static function booted(): void
    {
        static::creating(function (FacturaItem $item): void {
            if ($item->costo_unitario !== null || ! $item->stock_id || ! Esquema::tiene('factura_items', 'costo_unitario')) {
                return;
            }
            $esCompra = Factura::whereKey($item->factura_id)->value('tipo_movimiento') === 'compra';
            $item->costo_unitario = $esCompra
                ? (float) $item->precio_unitario
                : (float) (Stock::whereKey($item->stock_id)->value('precio_compra') ?? 0);
        });

        // Al editar una factura: si cambia el producto (venta) o el precio pagado (compra),
        // el costo guardado debe seguir al nuevo dato; si no, la utilidad queda calculada
        // con el costo de otro producto.
        static::updating(function (FacturaItem $item): void {
            if (! $item->isDirty(['stock_id', 'precio_unitario']) || ! Esquema::tiene('factura_items', 'costo_unitario')) {
                return;
            }
            $esCompra = Factura::whereKey($item->factura_id)->value('tipo_movimiento') === 'compra';
            if ($esCompra) {
                $item->costo_unitario = $item->stock_id ? (float) $item->precio_unitario : null;
            } elseif ($item->isDirty('stock_id')) {
                $item->costo_unitario = $item->stock_id
                    ? (float) (Stock::whereKey($item->stock_id)->value('precio_compra') ?? 0)
                    : null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->cantidad * (float) $this->precio_unitario;
    }

    /** Utilidad bruta del ítem con el costo histórico, o null si no se conoce el costo. */
    public function getUtilidadAttribute(): ?float
    {
        if ($this->costo_unitario === null) {
            return null;
        }

        return round((int) $this->cantidad * ((float) $this->precio_unitario - (float) $this->costo_unitario), 2);
    }
}
