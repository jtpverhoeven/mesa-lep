<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'html'])]
class ConfirmationTable extends Model
{
    protected $table = 'confirmationtables';

    public $timestamps = false;
}
