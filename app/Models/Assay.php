<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'original_id', 'name', 'type_base', 'media_id', 'dillution', 'replicates',
    'confirmation', 'confirmation_type', 'confirmation_script', 'confirmation_support',
    'type', 'meta_assays', 'max_count', 'min_count', 'script', 'custom_fields',
    'duration', 'start_from', 'active', 'hide_report', 'show_conf_table',
    'confirmation_init', 'confirmation_depth', 'uses_indicator', 'uses_trip_indicator',
    'article_code', 'billable',
])]
class Assay extends Model
{
    protected $table = 'assays';

    public $timestamps = false;

    public function assayType(): BelongsTo
    {
        return $this->belongsTo(AssayType::class, 'type_base');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'original_id', 'original_id')->orderBy('id');
    }

    public function matrixContents(): HasMany
    {
        return $this->hasMany(MatrixContent::class, 'assay_base', 'original_id');
    }

    public function matrices(): BelongsToMany
    {
        return $this->belongsToMany(Matrix::class, 'matrixcontent', 'assay_base', 'matrix', 'original_id', 'id')
            ->withPivot('id');
    }
}
