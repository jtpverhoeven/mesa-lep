<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'reference', 'client', 'subclient', 'project_name', 'project_notes', 'project_date',
    'last_edit', 'custom_fields', 'revision', 'auth_status', 'auth_by', 'auth_on',
    'print_version', 'predicted_end', 'project_extra', 'is_ready', 'special_type',
    'rap_stat', 'rap_by', 'rap_on', 'rap_rev', 'became_ready_on', 'started', 'added_by',
    'portal_id', 'locked', 'locked_by', 'lock_pass', 'lock_message', 'print_info',
])]
class Project extends Model
{
    protected $table = 'projects';

    public $timestamps = false;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'project');
    }

    public function sampleAnalyses(): HasMany
    {
        return $this->hasMany(SampleAnalysis::class, 'project')->orderBy('project_order');
    }

    public function metadata(): HasManyThrough
    {
        return $this->hasManyThrough(Metadata::class, Sample::class, 'project', 'sample')
            ->orderBy('metadata.meta_order')
            ->orderBy('metadata.id');
    }
}
