<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'alias', 'position'])]
class SampleProcedureField extends Model
{
    protected $table = 'sampleprocedurefields';

    public $timestamps = false;
}