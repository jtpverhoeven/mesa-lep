<?php

namespace App\Actions\Reports;

use App\Actions\SampleFiles\ListProjectClientFiles;
use App\Models\Export;
use App\Models\Project;
use App\Models\SampleFile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class BuildReportSenderFiles
{
    public function __construct(
        private ListProjectClientFiles $files,
        private ResolveExportSampleScope $scope,
    ) {}

    /**
     * @param  Collection<int, Export>  $exports
     * @return array{attachments: EloquentCollection<int, SampleFile>, orphaned: EloquentCollection<int, SampleFile>}
     */
    public function handle(Project $project, Collection $exports): array
    {
        $sampleIds = $exports->flatMap(fn (Export $export): array => $this->scope->handle($export))->unique()->values()->all();
        $visibleFiles = $this->files->handle($project);

        // Exports without any sample scope predate scoping: every visible file belongs to the report.
        if ($sampleIds === []) {
            return ['attachments' => $visibleFiles, 'orphaned' => new EloquentCollection];
        }

        $attachments = $this->files->handle($project, $sampleIds);
        $attachmentHashes = $attachments->pluck('hash_name')->all();

        return [
            'attachments' => $attachments,
            'orphaned' => $visibleFiles->reject(fn (SampleFile $file): bool => in_array($file->hash_name, $attachmentHashes, true))->values(),
        ];
    }

    /**
     * @param  Collection<int, Export>  $exports
     * @return array{attachments: list<array<string, mixed>>, orphaned: list<array<string, mixed>>}
     */
    public function rows(Project $project, Collection $exports): array
    {
        $files = $this->handle($project, $exports);

        return [
            'attachments' => $this->files->rows($files['attachments']),
            'orphaned' => $this->files->rows($files['orphaned']),
        ];
    }
}
