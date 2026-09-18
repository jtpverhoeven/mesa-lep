<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $table = 'portal';

    public $timestamps = false;

    protected $fillable = [
        'pk',
        'pv',
    ];

    protected $hidden = [
        'pv',
    ];
}
