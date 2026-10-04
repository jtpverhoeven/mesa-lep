<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'common_name', 'common_name_en', 'alertable', 'active', 'selectable', 'border_reaction',
])]
class PortalAssay extends Model
{
    protected $table = 'portalassays';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'alertable' => 'integer',
            'active' => 'integer',
            'selectable' => 'integer',
            'border_reaction' => 'integer',
        ];
    }

    public function assayContents(): HasMany
    {
        return $this->hasMany(PortalAssayContent::class, 'common_id');
    }

    public function assays(): BelongsToMany
    {
        return $this->belongsToMany(Assay::class, 'portalassaycontent', 'common_id', 'assay_id')
            ->withPivot(['id', 'original_id']);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_portal_assay', 'portal_assay_id', 'client_id')
            ->withPivot('id');
    }
}
