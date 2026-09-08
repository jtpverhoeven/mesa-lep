<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'standard_value', 'position', 'active'])]
class AssayField extends Model
{
    protected $table = 'assayfields';

    public $timestamps = false;
}
