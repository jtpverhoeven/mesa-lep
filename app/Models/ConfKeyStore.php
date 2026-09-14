<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['innocdate', 'media', 'param', 'value'])]
class ConfKeyStore extends Model
{
    protected $table = 'confkeystore';

    public $timestamps = false;
}
