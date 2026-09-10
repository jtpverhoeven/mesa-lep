<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'original_id', 'name', 'global', 'client', 'active', 'portal_visible', 'lims_visible',
])]
class ResearchProfile extends Model
{
    protected $table = 'researchprofiles';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'global' => 'boolean',
            'active' => 'boolean',
            'portal_visible' => 'boolean',
            'lims_visible' => 'boolean',
        ];
    }

    public function clientRecord(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'original_id', 'original_id')->orderBy('id');
    }

    public function assayProfiles(): HasMany
    {
        return $this->hasMany(AssayProfile::class, 'research_profile')->orderBy('project_order');
    }

    public function sampleAnalyses(): HasMany
    {
        return $this->hasMany(SampleAnalysis::class, 'profile');
    }
}
