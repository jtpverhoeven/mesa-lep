<?php

namespace App\Actions\Printing;

use App\Models\Result;
use App\Models\SampleAnalysis;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;

class GetAnalysisLabelPrintContext
{
    public function __construct(private GetLabelPrintContext $getLabelPrintContext) {}

    /**
     * @param  array<string, mixed>|null  $sampleContext
     * @return array<string, mixed>
     */
    public function handle(SampleAnalysis $analysis, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, ?array $sampleContext = null, int|bool|null $restrictFollowNumber = null): array
    {
        $analysis->loadMissing(['sampleRecord', 'assayRecord', 'results']);

        if ($analysis->sampleRecord === null || $analysis->assayRecord === null) {
            throw new LogicException('Sample analysis #'.$analysis->id.' references a missing sample or assay.');
        }

        $clock = DateTimeImmutable::createFromInterface($printedAt ?? now());
        $context = $sampleContext ?? $this->getLabelPrintContext->handle($analysis->sampleRecord);
        $context['analysis'] = $analysis->getAttributes();
        $context['assay'] = $analysis->assayRecord->getAttributes();
        $context['analysis_labels'] = [];
        $end = $clock->setTimestamp($clock->getTimestamp() + (int) (($dateOffset + (float) $analysis->assayRecord->duration) * 86400));
        $days = ['Mon' => 'MA', 'Tue' => 'DI', 'Wed' => 'WO', 'Thu' => 'DO', 'Fri' => 'VR', 'Sat' => 'ZA', 'Sun' => 'ZO'];
        $results = $analysis->results->sort(fn (Result $left, Result $right): int => ((float) $left->df <=> (float) $right->df) ?: ((int) $left->follow_no <=> (int) $right->follow_no));

        foreach ($results as $result) {
            if ($restrictFollowNumber !== null && (int) $result->follow_no != $restrictFollowNumber) {
                continue;
            }

            $dilution = rtrim(rtrim(sprintf('%.10F', (float) $result->df), '0'), '.');
            $decimal = explode('.', $dilution, 2)[1] ?? '';
            $context['analysis_labels'][] = [
                'dillution_label' => $decimal === '' ? '' : '-'.strlen($decimal),
                'dillution_numeric' => $dilution,
                'predicted_end_day' => $days[$end->format('D')],
                'plate_barcode' => $analysis->sampleRecord->barcode.'.'.$analysis->follow_number.'.'.$result->follow_no,
                'plate_barcode_sample' => $analysis->sampleRecord->barcode,
            ];
        }

        return $context;
    }
}
