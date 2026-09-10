<?php

namespace App\Actions\Samples;

use App\Models\Cvar;
use App\Models\Sample;
use Carbon\CarbonInterface;

class BarcodeGenerator
{
    public function predict(int $offset = 0, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $lastSample = Sample::query()
            ->where('sample_type', '!=', 'L')
            ->latest('id')
            ->first(['follow_no', 'date_registered']);

        return $this->forLastSample($lastSample, $at, $offset);
    }

    public function forLastSample(?Sample $lastSample, CarbonInterface $at, int $offset = 0): string
    {
        return $this->format($this->nextFollowNumber($lastSample, $at, $offset), $at);
    }

    public function followNumberForLastSample(?Sample $lastSample, CarbonInterface $at, int $offset = 0): int
    {
        return $this->nextFollowNumber($lastSample, $at, $offset);
    }

    private function nextFollowNumber(?Sample $lastSample, CarbonInterface $at, int $offset): int
    {
        $start = (int) $this->setting('MESA_BAR_START', '1000');

        if ($lastSample === null) {
            return $start + $offset;
        }

        $followNumber = ((int) $lastSample->follow_no) + 1;
        $lastRegisteredAt = $at->copy()->setTimestamp((int) $lastSample->date_registered);

        if ($this->shouldReset($lastRegisteredAt, $at)) {
            $followNumber = $start;
        }

        return $followNumber + $offset;
    }

    private function shouldReset(CarbonInterface $lastRegisteredAt, CarbonInterface $at): bool
    {
        return match ($this->setting('MESA_BAR_RESET', 'Y')) {
            'Y' => $lastRegisteredAt->year < $at->year,
            'M' => $lastRegisteredAt->month < $at->month,
            'D' => $lastRegisteredAt->day < $at->day,
            default => false,
        };
    }

    private function format(int $followNumber, CarbonInterface $at): string
    {
        $prefix = match ($this->setting('MESA_BAR_PREFIX', 'YYMM')) {
            'MMYY' => $at->format('my'),
            'YYMM' => $at->format('ym'),
            default => '',
        };

        return $prefix.$followNumber;
    }

    private function setting(string $name, string $default): string
    {
        return Cvar::query()
            ->where('cvar', $name)
            ->value('value') ?: $default;
    }
}