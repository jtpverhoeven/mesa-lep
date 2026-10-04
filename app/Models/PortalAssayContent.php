<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assay_id', 'common_id', 'original_id'])]
class PortalAssayContent extends Model
{
    protected $table = 'portalassaycontent';

    public $timestamps = false;

    public function portalAssay(): BelongsTo
    {
        return $this->belongsTo(PortalAssay::class, 'common_id');
    }

    public function assay(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay_id');
    }
}
