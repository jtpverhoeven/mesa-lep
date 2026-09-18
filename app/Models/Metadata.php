<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sample', 'name', 'value', 'meta_data_key_id', 'meta_order'])]
class Metadata extends Model
{
    protected $table = 'metadata';

    public $timestamps = false;

    public function sampleRecord(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample');
    }
}
