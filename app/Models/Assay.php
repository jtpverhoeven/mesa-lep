<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'original_id', 'name', 'type_base', 'media_id', 'dillution', 'replicates',
    'confirmation', 'confirmation_type', 'confirmation_script', 'confirmation_support',
    'type', 'meta_assays', 'max_count', 'min_count', 'script', 'custom_fields',
    'duration', 'start_from', 'active', 'hide_report', 'show_conf_table',
    'confirmation_init', 'confirmation_depth', 'uses_indicator', 'uses_trip_indicator',
    'article_code', 'billable',
])]
class Assay extends Model
{
    protected $table = 'assays';

    public $timestamps = false;

    public function assayType(): BelongsTo
    {
        return $this->belongsTo(AssayType::class, 'type_base');
    }

    /** A result row's data with every field of the assay type present but empty. */
    public function emptyResultData(): object
    {
        return (object) ($this->assayType?->fields
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [$name => ''])
            ->all() ?? []);
    }

    /**
     * The media this assay lists, from the legacy JSON array or a single bare id.
     *
     * @return list<int>
     */
    public function mediaIds(): array
    {
        $value = $this->media_id;

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($value)) {
            return is_numeric($value) ? [(int) $value] : [];
        }

        return collect($value)
            ->map(fn (mixed $id): int => is_array($id) ? (int) ($id['mediaId'] ?? 0) : (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'original_id', 'original_id')->orderBy('id');
    }

    public function matrixContents(): HasMany
    {
        return $this->hasMany(MatrixContent::class, 'assay_base', 'original_id');
    }

    public function matrices(): BelongsToMany
    {
        return $this->belongsToMany(Matrix::class, 'matrixcontent', 'assay_base', 'matrix', 'original_id', 'id')
            ->withPivot('id');
    }

    public function confirmationTables(): BelongsToMany
    {
        return $this->belongsToMany(ConfirmationTable::class, 'assay_confirmation_table');
    }

    public function assayProfiles(): HasMany
    {
        return $this->hasMany(AssayProfile::class, 'assay');
    }

    public function sampleAnalyses(): HasMany
    {
        return $this->hasMany(SampleAnalysis::class, 'assay_base');
    }

    public function roamingAnalyses(): HasMany
    {
        return $this->hasMany(RoamingAnalysis::class, 'assay');
    }

    public function portalAssays(): BelongsToMany
    {
        return $this->belongsToMany(PortalAssay::class, 'portalassaycontent', 'assay_id', 'common_id')
            ->withPivot(['id', 'original_id']);
    }

    public function docgenTemplates(): BelongsToMany
    {
        return $this->belongsToMany(DocgenTemplate::class, 'docgenassociations', 'assay_id', 'occurs_in')
            ->withPivot(['id', 'original_assay_id']);
    }
}
