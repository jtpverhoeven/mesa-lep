<?php

namespace App\Actions\Samples;

use App\Actions\AssuranceForms\GetOrCreateAssuranceForm;
use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Actions\AssuranceForms\SynchronizeAssuranceForm;
use App\Models\AssuranceForm;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterSampleInoculation
{
    public function __construct(
        private GetOrCreateAssuranceForm $getOrCreateForm,
        private QueueAssuranceFormSynchronization $queueAssuranceSync,
        private ResolveAssuranceDay $resolveDay,
        private SynchronizeAssuranceForm $synchronizeForm,
    ) {}

    public function handle(Sample $sample, bool $overwrite = false): Sample
    {
        $previousInoculation = '';

        $updated = DB::transaction(function () use ($sample, $overwrite, &$previousInoculation): Sample {
            $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            $project = (int) $lockedSample->project > 0
                ? Project::query()->lockForUpdate()->find($lockedSample->project)
                : null;

            if ($project?->locked || $project?->auth_status) {
                throw ValidationException::withMessages([
                    'sample' => 'Dit project is vergrendeld of geautoriseerd.',
                ]);
            }

            $previousInoculation = (string) ($lockedSample->sample_innoculated ?? '');

            if ($this->hasInoculation($previousInoculation) && ! $overwrite) {
                throw ValidationException::withMessages([
                    'already_started' => 'Dit monster is al ingezet. Nieuwe waarden toch opslaan?',
                ]);
            }

            $lockedSample->sample_innoculated = (string) now()->timestamp;
            $lockedSample->save();

            return $lockedSample->fresh();
        });

        $newFormDate = $this->resolveDay->handle($updated->sample_innoculated)['form_date'];
        $this->getOrCreateForm->handle($newFormDate);

        $affectedDates = [$newFormDate];
        $previousFormDate = $this->previousFormDate($previousInoculation);

        if ($previousFormDate !== null && $previousFormDate !== $newFormDate) {
            $previousTimestamp = $this->resolveDay->handle($previousFormDate)['timestamp'];
            $previousForm = AssuranceForm::query()->where('date', $previousTimestamp)->first();

            if ($previousForm !== null) {
                $this->synchronizeForm->handle($previousForm);
                $affectedDates[] = $previousFormDate;
            }
        }

        $this->queueAssuranceSync->handleMany($affectedDates);

        return $updated->fresh();
    }

    private function hasInoculation(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && $value !== '0';
    }

    private function previousFormDate(string $value): ?string
    {
        if (! $this->hasInoculation($value)) {
            return null;
        }

        try {
            return $this->resolveDay->handle($value)['form_date'];
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
