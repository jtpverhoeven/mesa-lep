<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'active', 'hide', 'fields'])]
class SampleProcedure extends Model
{
    protected $table = 'sampleprocedures';

    public $timestamps = false;
}