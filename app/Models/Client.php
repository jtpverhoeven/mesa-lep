<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference', 'name', 'title', 'fname', 'mname', 'lname', 'street_name',
    'street_number', 'postal_code', 'place', 'country', 'telephone', 'cellphone',
    'email', 'notes', 'attachment', 'active', 'category', 'trip_red', 'report_notes',
    'nvwa_number', 'debit_number',
])]
class Client extends Model
{
    protected $table = 'clients';

    public $timestamps = false;

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ClientCategory::class, 'categories_clients', 'client_id', 'clientcategory_id')
            ->withPivot('id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'client');
    }

    public function sampleBuffers(): HasMany
    {
        return $this->hasMany(SampleBuffer::class, 'client');
    }

    public function productGroups(): HasMany
    {
        return $this->hasMany(ProductGroup::class, 'client_id');
    }

    public function referenceSources(): HasMany
    {
        return $this->hasMany(ReferenceSource::class, 'client');
    }

    public function portalAssays(): BelongsToMany
    {
        return $this->belongsToMany(PortalAssay::class, 'client_portal_assay', 'client_id', 'portal_assay_id')
            ->withPivot('id');
    }
}
