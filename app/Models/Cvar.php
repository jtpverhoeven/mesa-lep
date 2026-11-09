<?php

namespace App\Models;

use App\Support\Cvars;
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

    protected static function booted(): void
    {
        static::saved(fn () => app(Cvars::class)->flush());
        static::deleted(fn () => app(Cvars::class)->flush());
    }
}
