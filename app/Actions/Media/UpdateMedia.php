<?php

namespace App\Actions\Media;

use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Models\AssuranceForm;
use App\Models\Media;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateMedia
{
    public function __construct(
        private readonly MediaAttributes $attributes,
        private QueueAssuranceFormSynchronization $queueAssuranceSync,
    ) {}

    public function handle(Media $media, array $data): Media
    {
        $formDates = AssuranceForm::query()->get(['date', 'data'])
            ->filter(fn (AssuranceForm $form): bool => array_key_exists((string) $media->id, $form->decodedData()[3] ?? []))
            ->map(fn (AssuranceForm $form): string => CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d'))
            ->all();

        $updated = DB::transaction(function () use ($media, $data): Media {
            $media->fill($this->attributes->from($data, $media));
            $media->save();

            return $media->fresh();
        });

        $this->queueAssuranceSync->handleMany($formDates);

        return $updated;
    }
}
