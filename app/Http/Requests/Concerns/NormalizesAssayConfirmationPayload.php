<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use JsonException;

trait NormalizesAssayConfirmationPayload
{
    private array $confirmationPayloadErrors = [];

    protected function normalizeAssayConfirmationPayload(): void
    {
        foreach (['confirmation_script', 'confirmation_support'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_array($value)) {
                continue;
            }

            if ($field === 'confirmation_support' && ($value === null || trim((string) $value) === '')) {
                $this->merge([$field => null]);

                continue;
            }

            if (! is_string($value)) {
                $this->markConfirmationPayloadInvalid($field);

                continue;
            }

            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->markConfirmationPayloadInvalid($field);

                continue;
            }

            if ($decoded === null && $field === 'confirmation_support') {
                $this->merge([$field => null]);

                continue;
            }

            if (! is_array($decoded)) {
                $this->markConfirmationPayloadInvalid($field);

                continue;
            }

            $this->merge([$field => $decoded]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->confirmationPayloadErrors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }

    private function markConfirmationPayloadInvalid(string $field): void
    {
        $label = $field === 'confirmation_script' ? 'script' : 'ondersteuning';
        $this->confirmationPayloadErrors[$field] = "Het bevestigings{$label} moet geldige JSON-arraydata bevatten.";
        $this->merge([$field => null]);
    }
}
