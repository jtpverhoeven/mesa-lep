<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['said', 'parameter', 'result', 'reason', 'disposition'])]
class VetoResult extends Model
{
    protected $table = 'vetoresults';

    public $timestamps = false;

    public function sampleAnalysis(): BelongsTo
    {
        return $this->belongsTo(SampleAnalysis::class, 'said');
    }
}
