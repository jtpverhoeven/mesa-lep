<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sample', 'sa_id', 'follow_no', 'profile', 'assay', 'assay_base',
    'roaming_id', 'df', 'rep', 'data',
])]
class Result extends Model
{
    protected $table = 'results';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function sampleRecord(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample');
    }

    public function sampleAnalysis(): BelongsTo
    {
        return $this->belongsTo(SampleAnalysis::class, 'sa_id');
    }

    public function assayRecord(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay_base');
    }
}
