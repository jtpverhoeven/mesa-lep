<?php

namespace App\Actions\AssuranceForms;

use App\Models\AssuranceForm;
use App\Support\Cvars;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class QueryFormsAwaitingData
{
    public function __construct(private Cvars $cvars) {}

    /**
     * Incomplete forms older than the BORG_TIME_BEFORE_CHECK cooldown. Legacy never applied BORG_TIME_CHECK_LIMIT.
     *
     * @return Builder<AssuranceForm>
     */
    public function handle(): Builder
    {
        $days = max(0, (int) ($this->cvars->value('BORG_TIME_BEFORE_CHECK') ?? 10));
        $cutoff = CarbonImmutable::now('Europe/Amsterdam')->subDays($days);

        return AssuranceForm::query()->awaitingData($cutoff->getTimestamp());
    }
}
