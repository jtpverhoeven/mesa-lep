<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'profile_group', 'sample', 'follow_number', 'profile', 'assay', 'assay_base',
    'roaming_id', 'predicted_end', 'original_assay_base', 'conf_requested',
    'is_ready', 'project', 'project_order', 'storedResult',
])]
class SampleAnalysis extends Model
{
    protected $table = 'sampleanalysis';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_ready' => 'boolean',
        ];
    }

    public function sampleRecord(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample');
    }

    public function projectRecord(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }

    public function researchProfile(): BelongsTo
    {
        return $this->belongsTo(ResearchProfile::class, 'profile');
    }

    public function assayProfile(): BelongsTo
    {
        return $this->belongsTo(AssayProfile::class, 'assay');
    }

    public function assayRecord(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay_base');
    }

    public function originalAssay(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'original_assay_base');
    }

    public function roamingAnalysis(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RoamingAnalysis::class, 'roaming_id');
    }

    public function roamingSettings(): HasOne
    {
        return $this->hasOne(\App\Models\RoamingAnalysis::class, 'said');
    }

    public function isRoaming(): bool
    {
        return (int) $this->profile === 0;
    }
}
