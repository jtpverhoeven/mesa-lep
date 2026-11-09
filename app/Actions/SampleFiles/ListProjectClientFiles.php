<?php

namespace App\Actions\SampleFiles;

use App\Models\Project;
use App\Models\SampleFile;
use Illuminate\Database\Eloquent\Collection;

class ListProjectClientFiles
{
    /**
     * @param  ?list<int>  $sampleIds  Null lists all visible project files; an empty list returns none.
     * @return Collection<int, SampleFile>
     */
    public function handle(Project $project, ?array $sampleIds = null): Collection
    {
        $query = $project->sampleFiles()
            ->visibleForClient()
            ->with('sample:id,barcode')
            ->withCount(['histories as sent_count']);

        if ($sampleIds !== null) {
            $sampleIds = array_values(array_unique($sampleIds));

            if ($sampleIds === []) {
                return new Collection;
            }

            $query->whereIn('samplefiles.sample_id', $sampleIds);
        }

        return $query->get()->each(fn (SampleFile $sampleFile) => $sampleFile->append('sent_label'));
    }

    /**
     * @param  Collection<int, SampleFile>  $sampleFiles
     * @return list<array{id: int, hash_name: string, barcode: ?string, original_name: string, sent_label: string, sent_count: int, created_at: ?string}>
     */
    public function rows(Collection $sampleFiles): array
    {
        return $sampleFiles->map(fn (SampleFile $sampleFile): array => [
            'id' => $sampleFile->id,
            'hash_name' => $sampleFile->hash_name,
            'barcode' => $sampleFile->sample?->barcode,
            'original_name' => $sampleFile->original_name,
            'sent_label' => $sampleFile->sent_label,
            'sent_count' => (int) $sampleFile->sent_count,
            'created_at' => $sampleFile->created_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i'),
        ])->values()->all();
    }
}
