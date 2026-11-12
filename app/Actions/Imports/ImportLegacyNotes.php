<?php

namespace App\Actions\Imports;

use App\Enums\NoteKind;
use App\Models\Note;
use App\Models\NoteTemplate;
use App\Models\Project;
use App\Models\Sample;
use App\Support\NoteHtml;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportLegacyNotes
{
    /**
     * Moves the legacy note columns and the legacy standard-note CSV into the notes tables.
     * Already transplanted notes and templates are left untouched, so the import can be re-run.
     *
     * @return array{sample_notes: int, project_notes: int, project_sample_notes: int, templates: int, skipped: list<string>}
     */
    public function handle(bool $dryRun = false, ?string $templatesPath = null): array
    {
        $summary = ['sample_notes' => 0, 'project_notes' => 0, 'project_sample_notes' => 0, 'templates' => 0, 'skipped' => []];
        $templates = $templatesPath === null ? [] : $this->readTemplates($templatesPath);

        DB::beginTransaction();

        try {
            $this->importSampleNotes($summary);
            $this->importProjectNotes($summary);
            $this->importTemplates($templates, $summary);
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        return $summary;
    }

    /** @param array{sample_notes: int, skipped: list<string>} $summary */
    private function importSampleNotes(array &$summary): void
    {
        Sample::query()->whereNotNull('sample_note')->where('sample_note', '!=', '')
            ->chunkById(500, function ($samples) use (&$summary): void {
                $imported = $this->importedNoteIds('sample', $samples->modelKeys(), NoteKind::Internal);

                foreach ($samples as $sample) {
                    if (trim($sample->sample_note) === '' || in_array($sample->id, $imported, true)) {
                        continue;
                    }

                    $this->createNote('sample', $sample->id, NoteKind::Internal, trim($sample->sample_note), $this->epoch($sample->date_registered) ?? $sample->updated_at);
                    $summary['sample_notes']++;
                }
            });
    }

    /** @param array{project_notes: int, project_sample_notes: int, skipped: list<string>} $summary */
    private function importProjectNotes(array &$summary): void
    {
        Project::query()->whereNotNull('project_notes')->where('project_notes', '!=', '')
            ->chunkById(500, function ($projects) use (&$summary): void {
                $projectImported = $this->importedNoteIds('project', $projects->modelKeys(), NoteKind::Report, anyOrigin: true);
                $sampleIds = Sample::query()->whereIn('project', $projects->modelKeys())->pluck('project', 'id');
                $sampleImported = $this->importedNoteIds('sample', $sampleIds->keys()->all(), NoteKind::Report, anyOrigin: true);

                foreach ($projects as $project) {
                    $notes = json_decode($project->project_notes, true);

                    if (! is_array($notes)) {
                        $summary['skipped'][] = "Project {$project->id}: project_notes is geen geldig JSON-object.";

                        continue;
                    }

                    $at = $this->epoch($project->last_edit) ?? $this->epoch($project->project_date);

                    foreach ($notes as $key => $text) {
                        $text = is_string($text) ? trim($text) : '';

                        if ($text === '') {
                            continue;
                        }

                        if ($key === 'project') {
                            if (! in_array($project->id, $projectImported, true)) {
                                $this->createNote('project', $project->id, NoteKind::Report, NoteHtml::fromPlainText($text), $at);
                                $summary['project_notes']++;
                            }

                            continue;
                        }

                        $sampleId = (int) $key;

                        if ((int) ($sampleIds[$sampleId] ?? 0) !== $project->id) {
                            $summary['skipped'][] = "Project {$project->id}: notitie voor onbekend monster {$key}.";
                        } elseif (! in_array($sampleId, $sampleImported, true)) {
                            $this->createNote('sample', $sampleId, NoteKind::Report, NoteHtml::fromPlainText($text), $at);
                            $summary['project_sample_notes']++;
                        }
                    }
                }
            });
    }

    /**
     * @param  list<array{name: string, content: string}>  $templates
     * @param  array{templates: int}  $summary
     */
    private function importTemplates(array $templates, array &$summary): void
    {
        foreach ($templates as $template) {
            $created = NoteTemplate::query()->firstOrCreate(
                ['name' => $template['name']],
                ['content' => NoteHtml::fromPlainText($template['content'])],
            );

            if ($created->wasRecentlyCreated) {
                $summary['templates']++;
            }
        }
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function importedNoteIds(string $type, array $ids, NoteKind $kind, bool $anyOrigin = false): array
    {
        return Note::query()
            ->where('notable_type', $type)
            ->whereIn('notable_id', $ids)
            ->ofKind($kind)
            ->when(! $anyOrigin, fn ($query) => $query->where('origin', Note::ORIGIN_LEGACY))
            ->pluck('notable_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function createNote(string $type, int $id, NoteKind $kind, string $body, ?Carbon $at): void
    {
        $note = new Note(['kind' => $kind, 'body' => $body, 'user_id' => null, 'origin' => Note::ORIGIN_LEGACY]);
        $note->notable_type = $type;
        $note->notable_id = $id;
        $note->created_at = $at ?? now();
        $note->updated_at = $at ?? now();
        $note->save();
    }

    private function epoch(mixed $value): ?Carbon
    {
        return is_numeric($value) && (int) $value > 0 ? Carbon::createFromTimestamp((int) $value) : null;
    }

    /** @return list<array{name: string, content: string}> */
    private function readTemplates(string $path): array
    {
        $absolute = str_starts_with($path, '/') ? $path : base_path($path);
        $handle = is_file($absolute) ? fopen($absolute, 'rb') : false;

        if ($handle === false) {
            throw new RuntimeException("Unable to open {$path}.");
        }

        $headers = fgetcsv($handle, escape: '');

        if (! is_array($headers) || array_diff(['name', 'content'], $headers) !== []) {
            fclose($handle);
            throw new RuntimeException('CSV must contain name,content headers.');
        }

        $templates = [];

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $record = array_combine($headers, $row);

            if (trim($record['name']) !== '' && trim($record['content']) !== '') {
                $templates[] = ['name' => mb_substr(trim($record['name']), 0, 128), 'content' => $record['content']];
            }
        }

        fclose($handle);

        return $templates;
    }
}
