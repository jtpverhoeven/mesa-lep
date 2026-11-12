<?php

namespace App\Console\Commands;

use App\Actions\Imports\ImportLegacyNotes;
use Illuminate\Console\Command;
use RuntimeException;

class ImportLegacyNotesCommand extends Command
{
    protected $signature = 'legacy:import-notes
                            {--templates=legacy/imports/projectnotes.csv : Legacy projectnotes CSV export (id,name,content); skipped when the file does not exist}
                            {--dry-run : Report what would be imported without writing}';

    protected $description = 'Transplant legacy sample notes, project notes and standard notes into the notes tables';

    public function handle(ImportLegacyNotes $import): int
    {
        $templates = (string) $this->option('templates');
        $templatesPath = is_file(str_starts_with($templates, '/') ? $templates : base_path($templates)) ? $templates : null;

        if ($templatesPath === null) {
            $this->warn("Templates CSV not found ({$templates}); standard notes are skipped.");
        }

        try {
            $summary = $import->handle((bool) $this->option('dry-run'), $templatesPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($summary['skipped'] as $message) {
            $this->warn($message);
        }

        $this->table(['Imported', 'Count'], [
            ['Sample notes (system)', $summary['sample_notes']],
            ['Project notes', $summary['project_notes']],
            ['Project sample notes', $summary['project_sample_notes']],
            ['Note templates', $summary['templates']],
        ]);
        $this->info($this->option('dry-run') ? 'Dry run complete; no rows written.' : 'Legacy notes imported.');

        return self::SUCCESS;
    }
}
