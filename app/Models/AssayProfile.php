<?php

namespace App\Models;

use App\Models\ReferenceSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_profile', 'assay', 'dillutions', 'replicates', 'reference', 'hidden',
    'project_order', 'conf_trip', 'reference_source',
])]
class AssayProfile extends Model
{
    protected $table = 'assayprofiles';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dillutions' => 'array',
            'reference' => 'array',
            'hidden' => 'boolean',
        ];
    }

    public function researchProfile(): BelongsTo
    {
        return $this->belongsTo(ResearchProfile::class, 'research_profile');
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