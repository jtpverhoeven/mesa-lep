<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'said', 'assay', 'dillutions', 'replicates', 'reference', 'reference_scope',
    'reference_source',
])]
class RoamingAnalysis extends Model
{
    protected $table = 'roaminganalysis';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dillutions' => 'array',
            'reference' => 'array',
        ];
    }

    public function sampleAnalysis(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SampleAnalysis::class, 'said');
    }

    public function assayRecord(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay');
    }

    public function referenceSource(): BelongsTo
    {
        return $this->belongsTo(ReferenceSource::class, 'reference_source');
    }
}
