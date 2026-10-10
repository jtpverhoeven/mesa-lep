<?php

namespace App\Actions\AssuranceForms;

use App\ChangeTracking\ChangeTracker;
use App\Models\AssuranceForm;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAssuranceFormField
{
    public function __construct(
        private RecalculateAssuranceForm $recalculate,
        private RefreshAffectedConfirmations $refreshConfirmations,
        private ChangeTracker $changeTracker,
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

                $previous = $data[1][$duration][$key];
                $data[1][$duration][$key] = $value;
            } else {
                if (! array_key_exists($key, $data[$section] ?? [])) {
                    throw ValidationException::withMessages(['field_key' => 'Dit borgingsveld bestaat niet.']);
                }

                $previous = $data[$section][$key];
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

            $labels = [
                'beheer' => 'Beheer monsteronderzoek', 'afgewogen' => 'Afgewogen door', 'ingezet' => 'Ingezet door',
                'gegoten' => 'Gegoten door', 'instoof' => 'Tijd platen in broedstoof',
                'datum_uitstoof' => 'Datum uit stoof, dag: ', 'tijd_uitstoof' => 'Tijd uit stoof, dag: ',
                'datum_aflezen' => 'Datum aflezen, dag: ', 'tijd_aflezen' => 'Tijd aflezen, dag: ',
                'afgelezen_door' => 'Afgelezen door, dag: ', 'tht_pfz' => 'THT PFZ',
                'tht_pfz_buizen' => 'THT PFZ buizen', 'tht_bpw' => 'THT BPW',
            ];
            $label = ($labels[$key] ?? $key).($section === 1 ? $duration : '');
            if ($section === 3) {
                $medium = Media::query()->find($mediaId);
                $label = $medium?->name ?? $key;
                if (preg_match('/^extra_\d+_(.+)$/', $key, $matches) === 1) {
                    $supplements = is_array($medium?->supplements) ? $medium->supplements : json_decode($medium?->supplements ?? '[]', true);
                    $supplement = collect($supplements)->firstWhere('supplementId', $matches[1]);
                    $label = 'Supplement: '.($supplement['name'] ?? $matches[1]).' voor media '.$label;
                }
            }
            $revisionTo = $value;
            if (in_array($key, ['beheer', 'afgewogen', 'ingezet', 'gegoten', 'afgelezen_door'], true)) {
                $users = $data['users_available_at_time'] ?? [];
                $previous = $users[$previous] ?? User::query()->find($previous)?->name ?? (string) $previous;
                $revisionTo = $users[$value] ?? User::query()->find($value)?->name ?? $value;
            }
            $this->changeTracker->changed(13, assuranceForm: $form->id,
                event: 'Veld gewijzigd: '.$label, from: $previous, to: $revisionTo);

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
