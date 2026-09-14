<?php

namespace App\Models;

use App\Casts\LegacyJsonObjectCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['said', 'note', 'in_use', 'racetrack', 'metadata', 'isReady', 'data'])]
class Confirmation extends Model
{
    protected $table = 'confirmations';

    public $timestamps = false;

    protected $attributes = [
        'in_use' => '{}',
        'racetrack' => '{}',
        'metadata' => '{}',
        'isReady' => 0,
        'data' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'in_use' => LegacyJsonObjectCast::class,
            'racetrack' => LegacyJsonObjectCast::class,
            'metadata' => LegacyJsonObjectCast::class,
            'isReady' => 'boolean',
            'data' => LegacyJsonObjectCast::class,
        ];
    }

    public function sampleAnalysis(): BelongsTo
    {
        return $this->belongsTo(SampleAnalysis::class, 'said');
    }
}
