<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'clientcategory_id'])]
class ClientCategoryAssignment extends Model
{
    protected $table = 'categories_clients';

    public $timestamps = false;
}