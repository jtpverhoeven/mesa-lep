<?php

namespace App\Actions\AssuranceForms;

use App\Jobs\SynchronizeAssuranceFormJob;

class QueueAssuranceFormSynchronization
{
    public function __construct(private ResolveAssuranceDay $resolveDay) {}

    public function handle(mixed $value): void
    {
        try {
            $formDate = $this->resolveDay->handle($value)['form_date'];
        } catch (\Throwable) {
            return;
        }

        SynchronizeAssuranceFormJob::dispatch($formDate)->afterCommit();
    }

    public function handleMany(iterable $values): void
    {
        $dates = [];

        foreach ($values as $value) {
            try {
                $dates[$this->resolveDay->handle($value)['form_date']] = true;
            } catch (\Throwable) {
                continue;
            }
        }

        foreach (array_keys($dates) as $formDate) {
            SynchronizeAssuranceFormJob::dispatch($formDate)->afterCommit();
        }
    }
}
