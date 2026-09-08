<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'icon', 'active'])]
class Matrix extends Model
{
    protected $table = 'matrix';

    public $timestamps = false;

    public function contents(): HasMany
    {
        return $this->hasMany(MatrixContent::class, 'matrix');
    }
}
