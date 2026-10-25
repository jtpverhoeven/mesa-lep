<?php

namespace App\Models;

use Database\Factories\DocgenAssociationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assay_id', 'original_assay_id', 'occurs_in'])]
class DocgenAssociation extends Model
{
    /** @use HasFactory<DocgenAssociationFactory> */
    use HasFactory;

    protected $table = 'docgenassociations';

    public $timestamps = false;

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocgenTemplate::class, 'occurs_in');
    }

    public function assay(): BelongsTo
    {
        return $this->belongsTo(Assay::class, 'assay_id');
    }
}
