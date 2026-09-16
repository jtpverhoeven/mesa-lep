<?php

namespace App\Actions\AssuranceForms;

use App\AssuranceForms\AssuranceValueAssessment;
use App\AssuranceForms\AssuranceValueRepository;
use App\Models\AssuranceForm;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;

class UpdateAssuranceExpiryEvidence
{
    public function __construct(
        private AssuranceValueAssessment $assessment,
        private AssuranceValueRepository $values,
        private RecalculateAssuranceForm $recalculate,
    ) {}

    public function handle(SampleAnalysis $analysis, int $mediaId, ?string $inoculationDate, ?string $expiryDate): void
    {
        $sample = $analysis->relationLoaded('sampleRecord') ? $analysis->sampleRecord : $analysis->sampleRecord()->first();
        $form = $sample === null ? null : $this->values->formForSample($sample);

        if ($sample === null || $form === null) {
            return;
        }

        DB::transaction(function () use ($form, $sample, $mediaId, $inoculationDate, $expiryDate): void {
            $form = AssuranceForm::query()->lockForUpdate()->findOrFail($form->getKey());
            $data = $form->decodedData();

            if ($this->assessment->isUsedAfterExpiry($inoculationDate, $expiryDate)) {
                $data[5]['wasOutOfDateHere'][$mediaId][$sample->id] = [
                    'sampleId' => (int) $sample->id,
                    'sampleBarcode' => (string) $sample->barcode,
                ];
            } else {
                unset($data[5]['wasOutOfDateHere'][$mediaId][$sample->id]);

                if (empty($data[5]['wasOutOfDateHere'][$mediaId])) {
                    unset($data[5]['wasOutOfDateHere'][$mediaId]);
                }
            }

            $form->setDecodedData($data);
            $form->save();
            $this->recalculate->handle($form);
        });
    }
}
