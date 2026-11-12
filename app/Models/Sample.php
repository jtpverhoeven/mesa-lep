<?php

namespace App\Models;

use App\Actions\Samples\EstimateRegisteredSampleEndpoints;
use App\Enums\NoteKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'barcode', 'tht_code', 'follow_no', 'description', 'client_description', 'sampling_method',
    'date_registered', 'registered_by', 'client', 'subclient', 'project', 'custom_fields',
    'predicted_end', 'sample_innoculated', 'stored_in', 'diluted_at', 'sample_note',
    'sample_type', 'leg_type', 'sample_extra', 'isEmpty', 'source', 'analyses_data',
    'portal_analyses', 'portal_sample_id', 'portal_product_group_id', 'portal_project_id',
    'portal_notes',
])]
class Sample extends Model
{
    protected $table = 'samples';

    const CREATED_AT = null;

    public function recalculatePredictedEnd(): int
    {
        $predictedEnd = app(EstimateRegisteredSampleEndpoints::class)->handle($this);
        $this->refresh();

        return $predictedEnd;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }

    public function samplingProcedure(): BelongsTo
    {
        return $this->belongsTo(SampleProcedure::class, 'sampling_method');
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'portal_product_group_id', 'portal_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(SampleAnalysis::class, 'sample')
            ->orderBy('project_order')
            ->orderBy('follow_number');
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(Metadata::class, 'sample')
            ->orderBy('meta_order')
            ->orderBy('id');
    }

    public function sampleFiles(): HasMany
    {
        return $this->hasMany(SampleFile::class, 'sample_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function internalNotes(): MorphMany
    {
        return $this->notes()->ofKind(NoteKind::Internal)->orderByDesc('notes.created_at')->orderByDesc('notes.id');
    }

    public function reportNote(): MorphOne
    {
        return $this->morphOne(Note::class, 'notable')->ofKind(NoteKind::Report);
    }
}
