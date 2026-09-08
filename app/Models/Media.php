<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'short_name', 'confirmation_media', 'type', 'supplements', 'hasDate',
    'confirmation_controls', 'active', 'used_for_prediction', 'prediction_qom',
    'prediction_default_quant', 'acceptable_range',
])]
class Media extends Model
{
    protected $table = 'media';

    public $timestamps = false;
}
