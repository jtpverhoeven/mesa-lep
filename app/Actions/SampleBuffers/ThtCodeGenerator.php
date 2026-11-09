<?php

namespace App\Actions\SampleBuffers;

use App\Models\Sample;
use App\Models\SampleBuffer;
use App\Support\Cvars;

class ThtCodeGenerator
{
    public function __construct(private Cvars $cvars) {}

    public function next(): string
    {
        $prefix = 'THT'.$this->datePrefix();
        $start = (int) ($this->cvars->value('MESA_BAR_START') ?: 1000);
        $highest = Sample::query()->where('tht_code', 'like', $prefix.'%')->pluck('tht_code')
            ->merge(SampleBuffer::query()->where('tht_code', 'like', $prefix.'%')->pluck('tht_code'))
            ->map(fn (?string $code): int => (int) substr((string) $code, strlen($prefix)))
            ->max();

        return $prefix.($highest ? $highest + 1 : $start);
    }

    private function datePrefix(): string
    {
        return match ($this->cvars->value('MESA_BAR_PREFIX') ?: 'YYMM') {
            'MMYY' => now()->format('my'),
            'YYMM' => now()->format('ym'),
            default => '',
        };
    }
}
