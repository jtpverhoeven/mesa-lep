<?php

namespace App\Models;

use Database\Factories\FooterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'lang', 'q', 'data'])]
class Footer extends Model
{
    /** @use HasFactory<FooterFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['q' => 'integer'];
    }
}
