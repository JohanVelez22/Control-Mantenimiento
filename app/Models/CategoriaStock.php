<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CategoriaStock extends Model
{
    use Auditable;

    protected $fillable = ['nombre', 'tipo'];
}
