<?php

namespace App\Http\Requests;

use App\Actions\PrintProfiles\ParsePrintProfile;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class SavePrintProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('labels.print-settings') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(ParsePrintProfile $parsePrintProfile): array
    {
        return [
            'name' => ['required', 'string', 'max:64'],
            'profile' => ['bail', 'required', 'string', 'max:65535', function (string $attribute, mixed $value, Closure $fail) use ($parsePrintProfile): void {
                try {
                    $parsePrintProfile->handle($value);
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'profielnaam', 'profile' => 'printscript'];
    }
}
