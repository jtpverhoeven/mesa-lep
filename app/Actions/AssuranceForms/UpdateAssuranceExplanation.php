<?php

namespace App\Actions\AssuranceForms;

use App\Models\AssuranceForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAssuranceExplanation
{
    public function __construct(
        private RecalculateAssuranceForm $recalculate,
        private RefreshAffectedConfirmations $refreshConfirmations,
    ) {}

    public function handle(AssuranceForm $form, string $fieldKey, string $explanation): AssuranceForm
    {
        $mediaId = null;
        $updated = DB::transaction(function () use ($form, $fieldKey, $explanation, &$mediaId): AssuranceForm {
            $form = AssuranceForm::query()->lockForUpdate()->findOrFail($form->getKey());
            $data = $form->decodedData();

            if (! $this->fieldExists($data, $fieldKey)) {
                throw ValidationException::withMessages(['field_key' => 'Dit borgingsveld bestaat niet.']);
            }

            $data[4][$fieldKey] = $explanation;
            $form->setDecodedData($data);
            $form->save();

            if (preg_match('/^b3_(?:extra_)?(\d+)/', $fieldKey, $matches) === 1) {
                $mediaId = (int) $matches[1];
            }

            $this->recalculate->handle($form);

            return $form->fresh();
        });

        if ($mediaId !== null) {
            $this->refreshConfirmations->handle($updated, $mediaId);
        }

        return $updated->fresh();
    }

    /** @param array<string|int, mixed> $data */
    private function fieldExists(array $data, string $fieldKey): bool
    {
        if (preg_match('/^b([023])_(.+)$/', $fieldKey, $matches) === 1) {
            return array_key_exists($matches[2], $data[(int) $matches[1]] ?? []);
        }

        if (preg_match('/^b1_(.+)_([^_]+)$/', $fieldKey, $matches) === 1) {
            return array_key_exists($matches[1], $data[1][$matches[2]] ?? []);
        }

        return false;
    }
}
