<?php

namespace App\Actions\AssuranceForms;

use App\AssuranceForms\AssuranceValueAssessment;
use App\Models\AssuranceForm;
use App\Models\Media;
use Carbon\CarbonImmutable;

class RecalculateAssuranceForm
{
    public function __construct(private AssuranceValueAssessment $assessment) {}

    /** @return array<int, string> */
    public function handle(AssuranceForm $form): array
    {
        $data = $form->decodedData();
        $missing = [];

        foreach ($data[0] ?? [] as $key => $value) {
            if ($this->assessment->valueIsMissing($value)) {
                $missing[] = 'Algemeen: '.$key;
            }
        }

        foreach ($data[1] ?? [] as $duration => $fields) {
            foreach (is_array($fields) ? $fields : [] as $key => $value) {
                if ($this->assessment->valueIsMissing($value)) {
                    $missing[] = 'Dag '.$duration.': '.$key;
                }
            }
        }

        foreach ($data[2] ?? [] as $key => $value) {
            if ($this->assessment->valueIsMissing($value) && ! in_array($key, ['tht_fraser', 'tht_bolton', 'tht_citraat'], true)) {
                $missing[] = $key;
            }

            if ($this->requiresExplanation($form, (string) $value, [], false) && empty($data[4]['b2_'.$key])) {
                $missing[] = 'Uitleg: '.$key;
            }
        }

        $media = $this->media($data[3] ?? []);

        foreach ($data[3] ?? [] as $key => $value) {
            if ($this->assessment->valueIsMissing($value)) {
                $missing[] = 'Media: '.$key;
            }

            $mediaId = $this->mediaId((string) $key);
            $hasEvidence = ! empty($data[5]['wasOutOfDateHere'][$mediaId]);

            if ($this->requiresExplanation($form, (string) $value, $media[$mediaId] ?? [], $hasEvidence)
                && empty($data[4]['b3_'.$key])) {
                $missing[] = 'Uitleg media: '.$key;
            }
        }

        $form->is_complete = $missing === [];
        $form->save();

        return $missing;
    }

    public function isNotApplicable(mixed $value): bool
    {
        return $this->assessment->isNotApplicable($value);
    }

    /** @param array<string, mixed> $media */
    private function requiresExplanation(AssuranceForm $form, string $value, array $media, bool $hasEvidence): bool
    {
        if ($this->assessment->valueIsMissing($value) || $this->assessment->isNotApplicable($value)) {
            return false;
        }

        $formDate = CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d');

        return $hasEvidence || $this->assessment->isOutOfSpecification($value, $formDate, $media);
    }

    /** @param array<string|int, mixed> $fields @return array<int, array<string, mixed>> */
    private function media(array $fields): array
    {
        $ids = collect(array_keys($fields))->map(fn (mixed $key): int => $this->mediaId((string) $key))->filter()->unique();

        return Media::query()->whereIn('id', $ids)->get()->mapWithKeys(fn (Media $medium): array => [
            (int) $medium->id => ['type' => (int) $medium->type, 'acceptable_range' => (string) ($medium->acceptable_range ?? '')],
        ])->all();
    }

    private function mediaId(string $key): int
    {
        return preg_match('/^extra_(\d+)_/', $key, $matches) === 1 ? (int) $matches[1] : (int) $key;
    }
}
