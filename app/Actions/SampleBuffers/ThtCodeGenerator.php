<?php

namespace App\Actions\SampleBuffers;

use App\Models\Cvar;
use App\Models\Sample;
use App\Models\SampleBuffer;

class ThtCodeGenerator
{
    public function next(): string
    {
        $prefix = 'THT'.$this->datePrefix();
        $start = (int) (Cvar::query()->where('cvar', 'MESA_BAR_START')->value('value') ?: 1000);
        $highest = Sample::query()->where('tht_code', 'like', $prefix.'%')->pluck('tht_code')
            ->merge(SampleBuffer::query()->where('tht_code', 'like', $prefix.'%')->pluck('tht_code'))
            ->map(fn (?string $code): int => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.($highest ? $highest + 1 : $start);
    }

    private function datePrefix(): string
    {
        return match (Cvar::query()->where('cvar', 'MESA_BAR_PREFIX')->value('value') ?: 'YYMM') {
            'MMYY' => now()->format('my'),
            'YYMM' => now()->format('ym'),
            default => '',
        };
    }
}
