<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'portal_id', 'name', 'default', 'visible'])]
class ProductGroup extends Model
{
    protected $table = 'productgroups';

    public $timestamps = false;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'portal_product_group_id', 'portal_id');
    }
}
