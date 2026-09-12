<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'hidden' => 'boolean',
        ];
    }

    protected function dillutions(): Attribute
    {
        return $this->legacyJsonArray();
    }

    protected function reference(): Attribute
    {
        return $this->legacyJsonArray();
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

    public function sampleAnalyses(): HasMany
    {
        return $this->hasMany(SampleAnalysis::class, 'assay')->where('profile', '!=', 0);
    }

    private function legacyJsonArray(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                for ($depth = 0; $depth < 2 && is_string($value); $depth++) {
                    $value = json_decode($value, true);
                }

                return is_array($value) ? $value : [];
            },
            set: fn (mixed $value): string => json_encode(
                is_array($value) ? $value : [],
                JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR,
            ),
        );
    }
}
