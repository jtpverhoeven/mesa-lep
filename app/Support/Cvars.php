<?php

namespace App\Support;

use App\Models\Cvar;
use Illuminate\Support\Collection;

/**
 * In-memory registry of all configuration variables (cvars).
 *
 * Bound as a scoped singleton: one instance per request, job or command, so
 * long-running workers never serve stale values. The table is loaded lazily on
 * first access and flushed whenever a Cvar is saved or deleted through Eloquent.
 */
class Cvars
{
    /** @var Collection<string, Cvar>|null */
    private ?Collection $items = null;

    /**
     * @return Collection<string, Cvar>
     */
    public function all(): Collection
    {
        return $this->items ??= Cvar::query()->orderBy('id')->get()->unique('cvar')->keyBy('cvar');
    }

    public function find(string $name): ?Cvar
    {
        return $this->all()->get($name);
    }

    public function value(string $name): ?string
    {
        return $this->find($name)?->value;
    }

    /**
     * The stored value, falling back to the cvar's own default and then to $fallback.
     */
    public function valueOrDefault(string $name, ?string $fallback = null): ?string
    {
        $cvar = $this->find($name);

        return $cvar?->value ?? $cvar?->default ?? $fallback;
    }

    /**
     * @return array<mixed>
     */
    public function json(string $name): array
    {
        $decoded = json_decode((string) $this->value($name), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, string>  $attributes  Extra columns (default, description) to write alongside the value.
     */
    public function set(string $name, string $value, array $attributes = []): Cvar
    {
        return Cvar::query()->updateOrCreate(['cvar' => $name], ['value' => $value] + $attributes);
    }

    public function flush(): void
    {
        $this->items = null;
    }
}
