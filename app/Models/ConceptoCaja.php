<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class ConceptoCaja extends Model
{
    use Auditable;

    protected $fillable = ['nombre'];

    /**
     * B-20: conceptos que el sistema busca por nombre al registrar abonos, ventas y
     * compras. Si se renombran, el sistema crea otro con el nombre original y los
     * reportes por concepto quedan partidos en dos; por eso no se renombran ni se borran.
     */
    public const DEL_SISTEMA = [
        'Abono Mantenimiento', 'Pago Mantenimiento',
        'Abono Electrónica', 'Pago Electrónica',
        'Venta de Inventario', 'Compra de Inventario',
    ];

    public function getEsDelSistemaAttribute(): bool
    {
        return in_array($this->getOriginal('nombre') ?? $this->nombre, self::DEL_SISTEMA, true);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'concepto_id');
    }
}
