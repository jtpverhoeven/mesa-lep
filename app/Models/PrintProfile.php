<?php

namespace App\Models;

use Database\Factories\PrintProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'profile'])]
class PrintProfile extends Model
{
    /** @use HasFactory<PrintProfileFactory> */
    use HasFactory;

    protected $table = 'printing';

    public $timestamps = false;
}
