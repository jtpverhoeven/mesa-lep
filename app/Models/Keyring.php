<?php

namespace App\Models;

use App\Keyrings\KeyringType;
use App\Support\AccountImages;
use Carbon\CarbonImmutable;
use Database\Factories\KeyringFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'type_id', 'issued', 'issued_to', 'expires'])]
class Keyring extends Model
{
    /** @use HasFactory<KeyringFactory> */
    use HasFactory;

    protected $table = 'keyrings';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'type_id' => 'integer',
            'issued' => 'integer',
            'issued_to' => 'integer',
            'expires' => 'integer',
        ];
    }

    /**
     * The legacy column stores the integer type as a string, so "0" must round-trip to the Sample case.
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?KeyringType => $value === null ? null : KeyringType::from((int) $value),
            set: fn (KeyringType|int|string $value): string => (string) ($value instanceof KeyringType ? $value->value : (int) $value),
        );
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_to');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('expires', '>=', now()->timestamp);
    }

    public function scopeForResource(Builder $query, KeyringType $type, int $typeId): void
    {
        $query->where('type', (string) $type->value)->where('type_id', $typeId);
    }

    public function isExpired(): bool
    {
        return $this->expires < now()->timestamp;
    }

    public function expiresAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($this->expires, config('app.timezone'));
    }

    /**
     * Holder-centric payload shared by broadcasts and the status shape.
     *
     * @return array{holder_id: int, name: string|null, avatar_url: string|null, lock_id: int, expires_at: string}
     */
    public function holderPayload(): array
    {
        $holder = $this->holder;

        return [
            'holder_id' => $this->issued_to,
            'name' => $holder?->name,
            'avatar_url' => $holder ? AccountImages::userAvatarUrl($holder) : null,
            'lock_id' => $this->id,
            'expires_at' => $this->expiresAt()->toIso8601String(),
        ];
    }

    public static function lifetimeSeconds(): int
    {
        return (int) (config('keyrings.lifetime_minutes') ?? config('session.lifetime', 120)) * 60;
    }

    public static function nextExpiry(): int
    {
        return now()->timestamp + self::lifetimeSeconds();
    }
}
