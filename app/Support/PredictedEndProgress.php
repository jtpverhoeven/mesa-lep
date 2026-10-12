<?php

namespace App\Support;

class PredictedEndProgress
{
    public function percentage(int|string|null $start, int|string|null $end, bool $authorized = false): int
    {
        if ($authorized) {
            return 100;
        }

        if (! is_numeric($start) || ! is_numeric($end) || (int) $end <= 0) {
            return 0;
        }

        $duration = (int) $end - (int) $start;

        return $duration <= 0 ? 0 : (int) max(0, min(100, ceil(
            (1 - (((int) $end - now()->timestamp) / $duration)) * 100,
        )));
    }
}
