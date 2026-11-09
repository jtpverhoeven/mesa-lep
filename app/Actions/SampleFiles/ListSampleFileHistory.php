<?php

namespace App\Actions\SampleFiles;

use App\Models\FileHistory;
use App\Models\SampleFile;
use Illuminate\Support\Arr;

class ListSampleFileHistory
{
    private const METHODS = [
        'report' => 'Samen met rapport',
        'filesender' => 'Via bestands mailer',
    ];

    /**
     * @return list<array{id: int, sent_on: ?string, method: string, sent_by: ?string, groups: list<string>, extra: string, url: ?string}>
     */
    public function handle(SampleFile $sampleFile): array
    {
        return $sampleFile->histories()
            ->with('sender:id,name')
            ->orderByDesc('sent_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FileHistory $history): array => [
                'id' => $history->id,
                'sent_on' => $history->sent_on?->timezone('Europe/Amsterdam')->format('d-m-Y H:i'),
                'method' => self::METHODS[$history->sent_method] ?? (string) $history->sent_method,
                'sent_by' => $history->sender?->name,
                'groups' => array_values(array_map('strval', Arr::wrap($history->sent_to['groups'] ?? []))),
                'extra' => implode(', ', Arr::wrap($history->sent_to['extra'] ?? [])),
                'url' => $history->sent_method === 'report' && $history->transaction_id !== null
                    ? route('reports.send.show', $history->transaction_id)
                    : null,
            ])->all();
    }
}
