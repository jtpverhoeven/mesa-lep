<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'client'])]
class ReferenceSource extends Model
{
    protected $table = 'referencesources';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    public function clientRecord(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function assayProfiles(): HasMany
    {
        return $this->hasMany(AssayProfile::class, 'reference_source');
    }

    public function scopeAvailableForClient(Builder $query, ?int $client): void
    {
        $query->where(function (Builder $query) use ($client) {
            $query->whereNull('client');

            if ($client !== null) {
                $query->orWhere('client', $client);
            }
        });
    }

    public function localizedName(string $locale = 'nl'): string
    {
        $names = $this->name ?? [];

        return (string) ($names[$locale] ?? $names['nl'] ?? $names['en'] ?? '');
    }
}