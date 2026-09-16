<?php

namespace App\Actions\AssuranceForms;

use App\AssuranceForms\AssuranceValueAssessment;
use App\Models\AssuranceForm;
use App\Models\Media;
use Carbon\CarbonImmutable;

class BuildAssuranceFormState
{
    public function __construct(private AssuranceValueAssessment $assessment, private RecalculateAssuranceForm $recalculate) {}

    public function handle(AssuranceForm $form): array
    {
        $missing = $this->recalculate->handle($form);
        $data = $form->decodedData();
        $media = $this->media($data[3] ?? []);
        $fields = [];

        foreach ([0, 1, 2, 3] as $section) {
            if ($section === 1) {
                foreach ($data[1] ?? [] as $duration => $durationFields) {
                    foreach (is_array($durationFields) ? $durationFields : [] as $key => $value) {
                        $fields[] = $this->field($form, $data, 1, (string) $key, (string) $value, $media, (string) $duration);
                    }
                }

                continue;
            }

            foreach ($data[$section] ?? [] as $key => $value) {
                $fields[] = $this->field($form, $data, $section, (string) $key, (string) $value, $media);
            }
        }

        $formDate = CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam');

        return [
            'id' => (int) $form->id,
            'form_date' => $formDate->format('Y-m-d'),
            'display_date' => $formDate->format('d-m-Y'),
            'timestamp' => (string) $form->date,
            'active' => true,
            'is_complete' => (bool) $form->is_complete,
            'missing' => $missing,
            'users' => is_array($data['users_available_at_time'] ?? null) ? $data['users_available_at_time'] : [],
            'fields' => $fields,
            'sections' => collect($fields)->groupBy('section')->map(fn ($rows): array => $rows->values()->all())->all(),
            'dynamic_sections' => collect([
                ['key' => 'media', 'label' => 'THT Media'],
                ['key' => 'confirmation_media', 'label' => 'THT Bevestigings Media'],
                ['key' => 'material', 'label' => 'Materiaal'],
            ])->map(fn (array $section): array => [...$section, 'fields' => collect($fields)->where('display_group', $section['key'])->values()->all(), 'rows' => $this->dynamicRows($fields, $section['key'])])->all(),
            'revisions' => [],
        ];
    }

    /** @param array<string|int, mixed> $data @param array<int, array<string, mixed>> $media */
    private function field(AssuranceForm $form, array $data, int $section, string $key, string $value, array $media, ?string $duration = null): array
    {
        $mediaId = $section === 3 ? $this->mediaId($key) : null;
        $mediaInfo = $media[$mediaId] ?? [];
        $fieldKey = 'b'.$section.'_'.$key.($section === 1 ? '_'.$duration : '');
        $samples = $mediaId === null ? collect() : collect($data[5]['wasOutOfDateHere'][$mediaId] ?? [])->sortKeys(SORT_NUMERIC);
        $formDate = CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d');
        $outOfSpecification = in_array($section, [2, 3], true) && ! $this->assessment->valueIsMissing($value)
            && ! $this->assessment->isNotApplicable($value) && $this->assessment->isOutOfSpecification($value, $formDate, $mediaInfo);
        $supplementId = preg_match('/^extra_\d+_(.+)$/', $key, $matches) === 1 ? $matches[1] : null;

        return [
            'key' => $fieldKey, 'section' => $section, 'kind' => $this->kind($section, $key, $mediaInfo),
            'label' => $this->label($section, $key, $mediaInfo), 'value' => $value,
            'explanation' => (string) ($data[4][$fieldKey] ?? ''), 'media_id' => $mediaId,
            'supplement_id' => $supplementId, 'duration' => $duration,
            'display_group' => $this->displayGroup($section, $mediaInfo), 'media_name' => $mediaInfo['name'] ?? null,
            'supplement_name' => $supplementId === null ? null : ($mediaInfo['supplements'][$supplementId] ?? null),
            'acceptable_range' => $mediaInfo['acceptable_range'] ?? null, 'out_of_specification' => $outOfSpecification,
            'out_of_date_here' => $samples->isNotEmpty(),
            'out_of_date_text' => $samples->map(fn (mixed $row, mixed $id): string => is_array($row) && ! empty($row['sampleBarcode']) ? (string) $row['sampleBarcode'] : 'Monster ID '.$id)->implode(', '),
            'requires_explanation' => ($outOfSpecification || $samples->isNotEmpty()) && ! $this->assessment->isNotApplicable($value),
        ];
    }

    /** @param array<string|int, mixed> $fields @return array<int, array<string, mixed>> */
    private function media(array $fields): array
    {
        $ids = collect(array_keys($fields))->map(fn (mixed $key): int => $this->mediaId((string) $key))->filter()->unique();

        return Media::query()->whereIn('id', $ids)->get()->mapWithKeys(function (Media $medium): array {
            $supplements = collect(is_string($medium->supplements) ? json_decode($medium->supplements, true) : $medium->supplements)
                ->filter(fn (mixed $row): bool => is_array($row) && isset($row['supplementId']))
                ->mapWithKeys(fn (array $row): array => [(string) $row['supplementId'] => (string) ($row['name'] ?? '')])->all();

            return [(int) $medium->id => ['name' => (string) $medium->name, 'type' => (int) $medium->type, 'confirmation_media' => (int) $medium->confirmation_media, 'acceptable_range' => (string) ($medium->acceptable_range ?? ''), 'supplements' => $supplements]];
        })->all();
    }

    private function mediaId(string $key): int
    {
        return preg_match('/^extra_(\d+)_/', $key, $matches) === 1 ? (int) $matches[1] : (int) $key;
    }

    private function kind(int $section, string $key, array $media): string
    {
        return $section === 0 ? (in_array($key, ['beheer', 'afgewogen', 'ingezet', 'gegoten'], true) ? 'user' : 'time') : ($section === 3 && (int) ($media['type'] ?? 0) === 3 ? 'material' : (str_contains($key, 'tijd') ? 'time' : 'date'));
    }

    private function displayGroup(int $section, array $media): ?string
    {
        return $section !== 3 ? null : ((int) ($media['type'] ?? 0) === 3 ? 'material' : ((int) ($media['confirmation_media'] ?? 0) === 1 ? 'confirmation_media' : 'media'));
    }

    private function label(int $section, string $key, array $media): string
    {
        $labels = ['beheer' => 'Beheer monsteronderzoek', 'afgewogen' => 'Afgewogen door', 'ingezet' => 'Ingezet door', 'gegoten' => 'Gegoten door', 'instoof' => 'Tijd platen in broedstoof', 'tht_pfz' => 'THT PFZ', 'tht_pfz_buizen' => 'THT PFZ buizen', 'tht_bpw' => 'THT BPW'];
        if ($section !== 3) {
            return $labels[$key] ?? $key;
        }
        if (preg_match('/^extra_\d+_(.+)$/', $key, $matches) === 1) {
            return ($media['supplements'][$matches[1]] ?? $matches[1]).' ('.($media['name'] ?? '').')';
        }

        return $media['name'] ?? 'Media '.$key;
    }

    private function dynamicRows(array $fields, string $group): array
    {
        return collect($fields)->where('display_group', $group)->groupBy('media_id')->map(function ($rows, mixed $mediaId): array {
            $parent = $rows->first(fn (array $field): bool => $field['supplement_id'] === null);

            return ['key' => (string) $mediaId, 'parent' => $parent, 'media_name' => (string) ($parent['media_name'] ?? $rows->first()['media_name'] ?? 'Media '.$mediaId), 'supplements' => $rows->filter(fn (array $field): bool => $field['supplement_id'] !== null)->map(fn (array $field): array => ['name' => (string) ($field['supplement_name'] ?: $field['label']), 'field' => $field])->values()->all(), 'has_warning' => $rows->contains(fn (array $field): bool => $field['out_of_specification'] || $field['out_of_date_here'])];
        })->values()->all();
    }
}
