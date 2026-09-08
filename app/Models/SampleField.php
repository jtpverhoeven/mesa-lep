<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'alias', 'type', 'std_value', 'position'])]
class SampleField extends Model
{
    use SoftDeletes;

    protected $table = 'samplefields';

    public $timestamps = false;
}