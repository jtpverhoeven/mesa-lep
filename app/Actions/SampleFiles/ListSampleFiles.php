<?php

namespace App\Actions\SampleFiles;

use App\Models\Sample;
use App\Models\SampleFile;

class ListSampleFiles
{
    /**
     * @return list<array<string, mixed>>
     */
    public function handle(Sample $sample): array
    {
        return $sample->sampleFiles()
            ->withCount('histories')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (SampleFile $file): array => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'created_at' => $file->created_at?->toIso8601String(),
                'visible_for_client' => $file->visible_for_client,
                'sent_to_client' => $file->sent_to_client,
                'sent_count' => $file->histories_count,
                'download_url' => route('samples.files.download', [$sample, $file]),
                'preview_url' => route('samples.files.preview', [$sample, $file]),
            ])
            ->all();
    }
}
