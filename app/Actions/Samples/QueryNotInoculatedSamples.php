<?php

namespace App\Actions\Samples;

use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

class QueryNotInoculatedSamples
{
    /**
     * Samples registered before the start of yesterday (lab time) that were not inoculated yet.
     *
     * @return Builder<Sample>
     */
    public function handle(): Builder
    {
        $cutoff = Date::now('Europe/Amsterdam')->subDay()->startOfDay();

        return Sample::query()->notInoculatedSince($cutoff->getTimestamp());
    }
}
