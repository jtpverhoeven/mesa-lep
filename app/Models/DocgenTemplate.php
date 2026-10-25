<?php

namespace App\Models;

use Database\Factories\DocgenTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'folder', 'active', 'view_order'])]
class DocgenTemplate extends Model
{
    /** @use HasFactory<DocgenTemplateFactory> */
    use HasFactory;

    protected $table = 'docgentemplates';

    public $timestamps = false;

    public const SUPPORTED_FOLDERS = ['maz2020'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'view_order' => 'integer'];
    }

    public function associations(): HasMany
    {
        return $this->hasMany(DocgenAssociation::class, 'occurs_in');
    }

    public function assays(): BelongsToMany
    {
        return $this->belongsToMany(Assay::class, 'docgenassociations', 'occurs_in', 'assay_id')
            ->withPivot(['id', 'original_assay_id']);
    }

    public function footers(): HasMany
    {
        return $this->hasMany(Footer::class, 'name', 'folder');
    }

    public function scopeInDisplayOrder(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN view_order IS NULL THEN 0 ELSE 1 END')
            ->orderBy('view_order')->orderBy('id');
    }

    public function isSupported(): bool
    {
        return in_array($this->folder, self::SUPPORTED_FOLDERS, true);
    }

    public function language(): string
    {
        return str_ends_with($this->folder, 'En') ? 'en' : 'nl';
    }

    public function acceptsBulk(): int
    {
        if (preg_match('/legionella|karkas|rodac/i', $this->name)) {
            return 2;
        }

        return stripos($this->folder, 'overview') !== false ? 1 : 0;
    }
}
