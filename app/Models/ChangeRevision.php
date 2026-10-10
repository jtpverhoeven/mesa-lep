<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'timestamp', 'type', 'assurance_form', 'project', 'sample', 'said', 'event', 'from', 'to'])]
class ChangeRevision extends Model
{
    protected $table = 'changetracker';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['timestamp' => 'string', 'type' => 'string', 'from' => 'string', 'to' => 'string'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projectRecord(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }

    public function sampleRecord(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample');
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(SampleAnalysis::class, 'said');
    }

    public function assuranceForm(): BelongsTo
    {
        return $this->belongsTo(AssuranceForm::class, 'assurance_form');
    }
}
