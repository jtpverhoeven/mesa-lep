<?php

namespace App\Actions\AssuranceForms;

use App\Models\AssuranceForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAssuranceFormField
{
    public function __construct(
        private RecalculateAssuranceForm $recalculate,
        private RefreshAffectedConfirmations $refreshConfirmations,
    ) {}

    public function handle(AssuranceForm $form, string $fieldKey, string $value): AssuranceForm
    {
        $mediaId = null;
        $updated = DB::transaction(function () use ($form, $fieldKey, $value, &$mediaId): AssuranceForm {
            $form = AssuranceForm::query()->lockForUpdate()->findOrFail($form->getKey());
            $data = $form->decodedData();
            [$section, $key, $duration] = $this->parseFieldKey($fieldKey);

            if ($section === 1) {
                if (! array_key_exists($duration, $data[1] ?? []) || ! array_key_exists($key, $data[1][$duration])) {
                    throw ValidationException::withMessages(['field_key' => 'Dit borgingsveld bestaat niet.']);
                }

                $data[1][$duration][$key] = $value;
            } else {
                if (! array_key_exists($key, $data[$section] ?? [])) {
                    throw ValidationException::withMessages(['field_key' => 'Dit borgingsveld bestaat niet.']);
                }

                $data[$section][$key] = $value;
            }

            if ($section === 3) {
                $mediaId = $this->mediaId($key);

                if ($this->recalculate->isNotApplicable($value)) {
                    unset($data[5]['wasOutOfDateHere'][$mediaId]);
                }
            }

            $form->setDecodedData($data);
            $form->save();
            $this->recalculate->handle($form);

            return $form->fresh();
        });

        if ($mediaId !== null) {
            $this->refreshConfirmations->handle($updated, $mediaId);
        }

        return $updated->fresh();
    }

    /** @return array{int, string, string} */
    private function parseFieldKey(string $fieldKey): array
    {
        if (preg_match('/^b([0-3])_(.+)$/', $fieldKey, $matches) !== 1) {
            throw ValidationException::withMessages(['field_key' => 'Dit borgingsveld is ongeldig.']);
        }

        $section = (int) $matches[1];
        $key = $matches[2];
        $duration = '';

        if ($section === 1) {
            $parts = explode('_', $key);
            $duration = (string) array_pop($parts);
            $key = implode('_', $parts);
        }

        return [$section, $key, $duration];
    }

    private function mediaId(string $key): int
    {
        return preg_match('/^extra_(\d+)_/', $key, $matches) === 1 ? (int) $matches[1] : (int) $key;
    }
}
