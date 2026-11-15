<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client', 'source', 'project', 'portal_follow_no', 'sampling_date', 'sampling_method',
    'sample_name', 'sample_details', 'tht', 'tht_date', 'meta', 'analyses_selected',
    'misc_directions', 'authorized', 'project_name', 'portal_order_info', 'portal_analyses',
    'portal_meta', 'portal_notes', 'portal_id', 'portal_product_group_id', 'portal_project',
    'receive_time', 'receive_date', 'tht_code', 'date_registered', 'sample_research_type',
    'sample_properties',
])]
class SampleBuffer extends Model
{
    protected $table = 'samplebuffers';

    public $timestamps = false;

    /** Rows waiting in the portal; accepted (authorized) THT rows live on the THT list instead. */
    public function scopeParked(Builder $query): void
    {
        $query->where('authorized', 0);
    }

    public function scopeAcceptedTht(Builder $query): void
    {
        $query->where('authorized', 1)->where('tht', 1);
    }

    /** Accepted THT studies whose THT date is before the given day (legacy: due by tomorrow). */
    public function scopeThtDueBefore(Builder $query, string $date): void
    {
        $query->acceptedTht()->where('tht_date', '<', $date);
    }

    public function clientRecord(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function samplingProcedure(): BelongsTo
    {
        return $this->belongsTo(SampleProcedure::class, 'sampling_method');
    }

    protected function casts(): array
    {
        return [
            'tht' => 'boolean',
            'authorized' => 'boolean',
            'tht_date' => 'date:Y-m-d',
            'meta' => 'array',
            'analyses_selected' => 'array',
            'portal_order_info' => 'array',
            'portal_analyses' => 'array',
            'portal_meta' => 'array',
            'sample_properties' => 'array',
        ];
    }
}
