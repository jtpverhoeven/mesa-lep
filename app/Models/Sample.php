<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(\App\Models\SampleAnalysis::class, 'sample')
            ->orderBy('project_order')
            ->orderBy('follow_number');
    }
}
