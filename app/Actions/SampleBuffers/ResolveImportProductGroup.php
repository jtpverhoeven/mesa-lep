<?php

namespace App\Actions\SampleBuffers;

use App\Models\ProductGroup;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Normalizer;

/**
 * Finds the product group a customer meant by the free text in their CSV.
 *
 * Legacy compared trimmed names exactly and never matched inactive groups. That stays the first
 * and preferred rule; case, whitespace and diacritic differences are tolerated only when they
 * point at exactly one group, so a typo-free export always resolves the same as before.
 */
class ResolveImportProductGroup
{
    /**
     * @param  Collection<int, ProductGroup>  $groups
     */
    public function handle(Collection $groups, string $text): ?ProductGroup
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $candidates = $groups->filter(fn (ProductGroup $group): bool => $group->visible === null || (int) $group->visible !== 0);

        return $candidates->first(fn (ProductGroup $group): bool => trim($group->name) === $text)
            ?? $this->uniqueMatch($candidates, $text, $this->fold(...))
            ?? $this->uniqueMatch($candidates, $text, fn (string $value): string => Str::ascii($this->fold($value)));
    }

    /**
     * @param  Collection<int, ProductGroup>  $candidates
     * @param  Closure(string): string  $normalize
     */
    private function uniqueMatch(Collection $candidates, string $text, Closure $normalize): ?ProductGroup
    {
        $needle = $normalize($text);
        $matches = $candidates->filter(fn (ProductGroup $group): bool => $normalize($group->name) === $needle);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function fold(string $value): string
    {
        $value = Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;

        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)) ?? $value);
    }
}
