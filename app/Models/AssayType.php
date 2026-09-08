<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'added_by', 'added_date', 'active'])]
class AssayType extends Model
{
    protected $table = 'assaytypes';

    public $timestamps = false;

    public function fields(): HasMany
    {
        return $this->hasMany(AssayTypeField::class, 'test_id')->orderBy('pos');
    }

    public function assays(): HasMany
    {
        return $this->hasMany(Assay::class, 'type_base');
    }
}
