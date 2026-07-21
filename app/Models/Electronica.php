<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Electronica extends Model
{
    use HasFactory, \App\Traits\Auditable;

    protected $fillable = [
        'id_orden',
        'equipo_id',
        'descripcion_problema',
        'tipo',
        'costo',
        'estado',
        'anulado',
        'fecha_entrada',
        'fecha_salida',
        'tecnico_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrada' => 'date',
            'fecha_salida'  => 'date',
        ];
    }

    /** Días transcurridos desde la entrada (o hasta la salida si está terminado) */
    public function getDiasTranscurridosAttribute(): int
    {
        $fin = ($this->estado === 'terminado' && $this->fecha_salida)
            ? $this->fecha_salida
            : Carbon::today();
        return (int) $this->fecha_entrada->diffInDays($fin);
    }

    public function getTotalAbonadoAttribute(): float
    {
        if ($this->relationLoaded('abonos')) {
            return (float) $this->abonos->sum('monto');
        }
        if (isset($this->attributes['total_abonado'])) {
            return (float) $this->attributes['total_abonado'];
        }
        return (float) $this->abonos()->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(0, (float) $this->costo - $this->total_abonado);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: excluye registros anulados.
     * Uso: Electronica::activos()->where(...)
     */
    public function scopeActivos($query)
    {
        return $query->where('anulado', false);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stocks()
    {
        return $this->belongsToMany(Stock::class, 'electronica_stock')
                    ->withPivot('cantidad', 'precio_unitario')
                    ->withTimestamps();
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }
}
