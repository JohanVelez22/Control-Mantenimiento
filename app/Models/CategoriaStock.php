<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaStock extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = ['nombre', 'tipo'];
}
