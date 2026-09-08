<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return $this->belongsToMany(\App\Models\ClientCategory::class, 'categories_clients', 'client_id', 'clientcategory_id')
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
}