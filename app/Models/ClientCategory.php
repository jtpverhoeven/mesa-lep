<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class ClientCategory extends Model
{
    protected $table = 'clientcategories';

    public $timestamps = false;

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Client::class, 'categories_clients', 'clientcategory_id', 'client_id')
            ->withPivot('id');
    }

    public function activeClients(): BelongsToMany
    {
        return $this->clients()->where('clients.active', 1);
    }
}