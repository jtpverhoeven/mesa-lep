<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assay_base', 'matrix'])]
class MatrixContent extends Model
{
    protected $table = 'matrixcontent';

    public $timestamps = false;

    public function originalAssay(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay_base');
    }

    public function matrixDefinition(): BelongsTo
    {
        return $this->belongsTo(Matrix::class, 'matrix');
    }
}
