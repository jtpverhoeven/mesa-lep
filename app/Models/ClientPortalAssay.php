<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'portal_assay_id'])]
class ClientPortalAssay extends Model
{
    protected $table = 'client_portal_assay';

    public $timestamps = false;
}
