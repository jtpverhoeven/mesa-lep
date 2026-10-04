<?php

namespace App\Actions\Samples;

use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Models\ResearchProfile;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CreateLegionellaSamples
{
    public function __construct(
        private CreateBulkSamples $create,
        private BulkSampleRegistrationOptions $options,
        private AddResearchProfileToSample $addProfile,
        private EstimateRegisteredSampleEndpoints $estimate,
        private QueueAssuranceFormSynchronization $queueAssuranceSync,
    ) {}

    public function handle(array $data): Collection
    {
        $profiles = collect($this->options->profiles())->keyBy('id');
        $data['special_type'] = 1;
        $data['sample_note'] = $data['sample_note'] ?? '';
        $data['project_extra'] = [];

        foreach ($data['samples'] as $index => &$row) {
            $profile = $profiles->get($row['profile_id']);
            if ($profile === null) {
                throw ValidationException::withMessages(['samples.'.$index.'.profile_id' => 'Kies een beschikbaar Legionella-profiel.']);
            }
            $matrix = $profile['matrix'];
            $row = [
                ...$row,
                'sample_type' => 'L',
                'leg_type' => $matrix,
                'description' => $this->options->value('LEGIONELLA_STD_DESCRIPTION', 'Legionella monster'),
                'custom_fields' => ['details' => ''],
                'sample_extra' => [
                    'follow' => (string) $row['follow'], 'type' => '', 'temperature' => '',
                    'filter_volume' => (int) $this->options->value('LEGIONELLA_FILTER_VOLUME_'.$matrix),
                ],
            ];
        }
        unset($row);

        return $this->create->handle($data, function (Sample $sample, array $row): void {
            $profile = ResearchProfile::query()->where(function ($query) use ($row) {
                $query->whereKey($row['profile_id'])->orWhere('original_id', $row['profile_id']);
            })->latest('id')->firstOrFail();

            $this->addProfile->handle($sample, $profile, legacyRegistration: true);
            $this->estimate->handle($sample);
            if (! empty($sample->sample_innoculated)) {
                $this->queueAssuranceSync->handle($sample->sample_innoculated);
            }
        });
    }
}
