<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class ConceptoCaja extends Model
{
    use Auditable;

    protected $fillable = ['nombre'];

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'concepto_id');
    }
}
