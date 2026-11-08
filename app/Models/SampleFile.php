<?php

namespace App\Models;

use Database\Factories\SampleFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sample_id', 'hash_name', 'original_name', 'visible_for_client', 'client_id', 'sent_to_client',
])]
class SampleFile extends Model
{
    /** @use HasFactory<SampleFileFactory> */
    use HasFactory;

    public const STORAGE_DIRECTORY = 'sample-files';

    protected $table = 'samplefiles';

    public const UPDATED_AT = null;

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FileHistory::class, 'samplefile_id');
    }

    public function storagePath(): string
    {
        return self::STORAGE_DIRECTORY.'/'.$this->hash_name;
    }

    public function scopeVisibleForClient(Builder $query): Builder
    {
        return $query->where('visible_for_client', true);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'visible_for_client' => 'boolean',
            'sent_to_client' => 'boolean',
        ];
    }
}
