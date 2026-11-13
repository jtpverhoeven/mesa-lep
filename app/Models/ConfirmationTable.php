<?php

namespace App\Models;

use Database\Factories\ConfirmationTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'html'])]
class ConfirmationTable extends Model
{
    /** @use HasFactory<ConfirmationTableFactory> */
    use HasFactory;

    protected $table = 'confirmationtables';

    public $timestamps = false;

    public function assays(): BelongsToMany
    {
        return $this->belongsToMany(Assay::class, 'assay_confirmation_table');
    }
}
