<?php

namespace App\Models;

use App\Support\Esquema;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use Auditable;

    protected $fillable = [
        'fecha', 'total_ingresos', 'total_egresos',
        'efectivo', 'efectivo_real_contado', 'diferencia',
        'consignacion', 'saldo_final', 'saldo_inicial', 'efectivo_esperado',
        'num_movimientos', 'bloqueado', 'observaciones', 'motivo_diferencia', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'bloqueado' => 'boolean',
            'efectivo_real_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    /**
     * Saldo inicial acumulado en efectivo al inicio del día del cierre. M-06: si el cierre
     * guardó su foto, se usa esa; los cierres anteriores a la migración se recalculan.
     */
    public function getSaldoInicialDiaAttribute(): float
    {
        if (($this->attributes['saldo_inicial'] ?? null) !== null) {
            return (float) $this->attributes['saldo_inicial'];
        }

        $fecha = $this->fecha ? $this->fecha->toDateString() : null;
        if (! $fecha) {
            return 0.0;
        }

        return static::saldoInicialEfectivo($fecha);
    }

    /**
     * Efectivo con el que arranca un día: el último conteo físico real anterior a la fecha
     * más el flujo en efectivo de los días intermedios. Se toma el último cierre CON conteo:
     * si el más reciente no se contó, usarlo como base descartaba el conteo real anterior
     * y el faltante de ese día se contaba dos veces.
     */
    public static function saldoInicialEfectivo(string $fecha): float
    {
        $flujoEfectivo = fn () => MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('tipo_pago', 'efectivo')
            ->where('fecha', '<', $fecha);
        $sumaNeta = "SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE -monto END) as total";

        // D-15 / M-06: con la foto del último cierre no hace falta recorrer la historia:
        // se parte de lo contado (o, si no se contó, de lo esperado) ese día.
        if (Esquema::tiene('cierre_cajas', 'efectivo_esperado')) {
            $ultimo = static::where('fecha', '<', $fecha)->orderBy('fecha', 'desc')->first();
            $base = $ultimo ? ($ultimo->efectivo_real_contado ?? $ultimo->getAttributes()['efectivo_esperado'] ?? null) : null;
            if ($ultimo && $base !== null) {
                $intermedio = (float) ($flujoEfectivo()->where('fecha', '>', $ultimo->fecha)->selectRaw($sumaNeta)->value('total') ?? 0);

                return (float) $base + $intermedio;
            }
        }

        $ultimoConteo = static::where('fecha', '<', $fecha)
            ->whereNotNull('efectivo_real_contado')
            ->orderBy('fecha', 'desc')
            ->first();

        if ($ultimoConteo) {
            $intermedio = (float) ($flujoEfectivo()->where('fecha', '>', $ultimoConteo->fecha)->selectRaw($sumaNeta)->value('total') ?? 0);

            return (float) $ultimoConteo->efectivo_real_contado + $intermedio;
        }

        return (float) ($flujoEfectivo()->selectRaw($sumaNeta)->value('total') ?? 0);
    }

    /**
     * Flujo neto del día en efectivo.
     */
    public function getFlujoDiaAttribute(): float
    {
        return (float) $this->efectivo;
    }

    /**
     * Efectivo total esperado en caja al momento del arqueo (saldo inicial + flujo del día).
     */
    public function getEfectivoEsperadoAttribute($valor): float
    {
        if ($valor !== null) {
            return (float) $valor;
        }

        return $this->saldo_inicial_dia + $this->flujo_dia;
    }

    /**
     * Estado de la conciliación: 'cuadrado', 'faltante', 'sobrante', o 'sin_conciliar'
     */
    public function getEstadoDiferenciaAttribute(): string
    {
        // `efectivo_real_contado` es la única señal fiable de que hubo conteo
        // físico. `diferencia` es NOT NULL DEFAULT 0, así que un cierre sin
        // conteo guarda 0 y NO debe leerse como un cuadre verificado.
        if ($this->efectivo_real_contado === null) {
            return 'sin_conciliar';
        }
        $dif = (float) $this->diferencia;
        if (abs($dif) < 0.01) {
            return 'cuadrado';
        }

        return $dif > 0 ? 'sobrante' : 'faltante';
    }

    /**
     * Clase de tarjeta canónica del sistema para este estado de conciliación.
     * Mantiene index/show homogeneous con el resto de tarjetas glass-card.
     */
    public function getEstadoDiferenciaCardAttribute(): string
    {
        return match ($this->estado_diferencia) {
            'cuadrado' => 'glass-card-emerald',
            'sobrante' => 'glass-card-blue',
            'faltante' => 'glass-card-red',
            default => 'glass-card-gray',
        };
    }

    /**
     * Texto corto del estado, usado en vistas y en el listado.
     */
    public function getEstadoDiferenciaLabelAttribute(): string
    {
        return match ($this->estado_diferencia) {
            'cuadrado' => 'Cuadrado',
            'sobrante' => 'Sobrante',
            'faltante' => 'Faltante',
            default => 'Sin conciliar',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
