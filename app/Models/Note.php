<?php

namespace App\Models;

use App\Enums\NoteKind;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['kind', 'body', 'user_id', 'origin'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    public const ORIGIN_LEGACY = 'legacy';

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'note_mentions');
    }

    public function scopeOfKind(Builder $query, NoteKind $kind): Builder
    {
        return $query->where('notes.kind', $kind);
    }

    protected function casts(): array
    {
        return ['kind' => NoteKind::class];
    }
}
