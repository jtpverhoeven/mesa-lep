<?php

namespace App\AssuranceForms;

use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Models\AssuranceForm;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Database\Eloquent\Collection;

class AssuranceValueRepository
{
    public function __construct(private ResolveAssuranceDay $resolveDay) {}

    public function valueForAnalysisAndMedia(SampleAnalysis $analysis, int $mediaId): ?string
    {
        $sample = $analysis->relationLoaded('sampleRecord') ? $analysis->sampleRecord : $analysis->sampleRecord()->first();
        $form = $sample === null ? null : $this->formForSample($sample);

        return $form === null ? null : $this->valueForForm($form, $mediaId);
    }

    /** @param Collection<int, int>|array<int, int> $mediaIds @return array<int, string> */
    public function valuesForSampleAndMedia(Sample $sample, Collection|array $mediaIds): array
    {
        $ids = collect($mediaIds)->map(fn (mixed $id): int => (int) $id)->unique();
        $data = $this->formForSample($sample)?->decodedData() ?? [];

        return $ids->mapWithKeys(fn (int $id): array => [$id => (string) ($data[3][$id] ?? '')])->all();
    }

    /** @return array{formId: int|false, value: string, outOfDateHere: bool, sampleText: string, explanation: string} */
    public function warningForAnalysisAndMedia(SampleAnalysis $analysis, int $mediaId): array
    {
        $sample = $analysis->relationLoaded('sampleRecord') ? $analysis->sampleRecord : $analysis->sampleRecord()->first();
        $form = $sample === null ? null : $this->formForSample($sample);

        if ($form === null) {
            return ['formId' => false, 'value' => '', 'outOfDateHere' => false, 'sampleText' => '', 'explanation' => ''];
        }

        $data = $form->decodedData();
        $samples = collect($data[5]['wasOutOfDateHere'][$mediaId] ?? [])->sortKeys(SORT_NUMERIC);

        return [
            'formId' => (int) $form->id,
            'value' => (string) ($data[3][$mediaId] ?? ''),
            'outOfDateHere' => $samples->isNotEmpty(),
            'sampleText' => $samples->map(fn (mixed $row, mixed $id): string => is_array($row) && ! empty($row['sampleBarcode']) ? (string) $row['sampleBarcode'] : 'Monster ID '.$id)->implode(', '),
            'explanation' => (string) ($data[4]['b3_'.$mediaId] ?? ''),
        ];
    }

    public function formForSample(Sample $sample): ?AssuranceForm
    {
        if (empty($sample->sample_innoculated)) {
            return null;
        }

        try {
            $timestamp = (string) $this->resolveDay->handle((string) $sample->sample_innoculated)['timestamp'];
        } catch (\Throwable) {
            return null;
        }

        return AssuranceForm::query()->where('date', $timestamp)->first();
    }

    public function valueForForm(AssuranceForm $form, int $mediaId): ?string
    {
        return isset($form->decodedData()[3][$mediaId]) ? (string) $form->decodedData()[3][$mediaId] : null;
    }
}
