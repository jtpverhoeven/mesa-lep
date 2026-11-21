<?php

namespace App\AssuranceForms;

use App\Models\AssuranceForm;
use Carbon\CarbonImmutable;

/**
 * Read-only view of the values an assurance form holds for one sample, flagged the way the form
 * flags them: out of specification, or a medium that was used for this sample after its THT date.
 */
class SampleAssuranceValues
{
    /** @var array<string|int, mixed> */
    private array $data;

    private CarbonImmutable $formDate;

    public function __construct(
        private AssuranceValueAssessment $assessment,
        public readonly AssuranceForm $form,
        private int $sampleId,
    ) {
        $this->data = $form->decodedData();
        $this->formDate = CarbonImmutable::createFromTimestamp((int) $form->date, AssuranceValueAssessment::TIMEZONE);
    }

    public function formDate(): CarbonImmutable
    {
        return $this->formDate;
    }

    public function value(int $section, string|int $key): string
    {
        return (string) ($this->data[$section][$key] ?? '');
    }

    /** @return array<string|int, mixed> */
    public function section(int $section, string|int $key): array
    {
        $values = $this->data[$section][$key] ?? [];

        return is_array($values) ? $values : [];
    }

    /** @return array{value: string, flagged: bool, out_of_specification: bool, used_after_expiry: bool, note: string} */
    public function date(int $section, string $key, ?int $mediaId = null): array
    {
        $value = $this->value($section, $key);

        return $this->flag($value, $this->assessment->isDateBeforeFormDate($value, $this->formDate), $mediaId, $section, $key);
    }

    /** @return array{value: string, flagged: bool, out_of_specification: bool, used_after_expiry: bool, note: string} */
    public function material(int $mediaId, string $acceptableRange): array
    {
        $value = $this->value(3, $mediaId);

        return $this->flag($value, $this->assessment->isMaterialOutOfRange($value, $acceptableRange), $mediaId, 3, (string) $mediaId);
    }

    /** @return array{value: string, flagged: bool, out_of_specification: bool, used_after_expiry: bool, note: string} */
    private function flag(string $value, bool $outOfSpecification, ?int $mediaId, int $section, string $key): array
    {
        $applicable = ! $this->assessment->isNotApplicable($value);
        $usedAfterExpiry = $applicable && $mediaId !== null && $this->wasUsedAfterExpiry($mediaId);
        $outOfSpecification = $applicable && $outOfSpecification;
        $flagged = $outOfSpecification || $usedAfterExpiry;
        $note = $flagged ? trim((string) ($this->data[4]['b'.$section.'_'.$key] ?? '')) : '';

        return [
            'value' => $value,
            'flagged' => $flagged,
            'out_of_specification' => $outOfSpecification,
            'used_after_expiry' => $usedAfterExpiry,
            'note' => $note,
        ];
    }

    private function wasUsedAfterExpiry(int $mediaId): bool
    {
        return ! empty($this->data[5]['wasOutOfDateHere'][$mediaId][$this->sampleId]);
    }
}
