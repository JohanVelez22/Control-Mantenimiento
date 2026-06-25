<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConceptoCaja extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = ['nombre'];

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'concepto_id');
    }
}
