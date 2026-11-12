<?php

namespace App\Rules;

use App\Support\DilutionCurve;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SequentialDilutions implements ValidationRule
{
    /** @param  string|null  $key  Read the dilution factor from this key when the entries are arrays. */
    public function __construct(private ?string $key = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $dilutions = array_map(
            fn (mixed $entry): mixed => $this->key !== null && is_array($entry) ? ($entry[$this->key] ?? null) : $entry,
            $value,
        );
        $problem = DilutionCurve::problem($dilutions);

        if ($problem !== null) {
            $fail($problem);
        }
    }
}
