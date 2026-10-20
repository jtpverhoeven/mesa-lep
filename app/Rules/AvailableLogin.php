<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class AvailableLogin implements ValidationRule
{
    public function __construct(private ?int $userId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('De inlognaam moet tekst zijn.');

            return;
        }

        $login = Str::lower($value);
        $exists = User::query()
            ->when($this->userId, fn ($query) => $query->whereKeyNot($this->userId))
            ->where(fn ($query) => $query
                ->whereRaw('lower(email) = ?', [$login])
                ->orWhereRaw('lower(alias) = ?', [$login]))
            ->exists();

        if ($exists) {
            $fail('Deze inlognaam is al in gebruik.');
        }
    }
}
