<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['test_id', 'name', 'alias', 'type', 'pos', 'endresults_driver', 'filter'])]
class AssayTypeField extends Model
{
    protected $table = 'assaytypefields';

    public $timestamps = false;

    public function assayType(): BelongsTo
    {
        return $this->belongsTo(AssayType::class, 'test_id');
    }
}
