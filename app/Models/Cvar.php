<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cvar extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cvar',
        'value',
        'default',
        'description',
    ];
}