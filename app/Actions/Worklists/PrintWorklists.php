<?php

namespace App\Actions\Worklists;

use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Models\Export;
use App\Models\Worklist;
use RuntimeException;
use ZipArchive;

class PrintWorklists
{
    public function __construct(
        private ResolveAssuranceDay $resolveDay,
        private SelectWorklistRows $select,
        private RenderWorklistPdf $render,
    ) {}

    /**
     * Prints one worklist, or a zip with every worklist that has samples when none is given.
     *
     * @return array{filename: string, mime: string, content: string}|null Null when no sample was inoculated that day.
     */
    public function handle(?Worklist $worklist, string $date): ?array
    {
        $day = $this->resolveDay->handle($date);
        $start = (int) $day['timestamp'];
        // Legacy treats the inoculation day as ending after 23 hours.
        $end = $start + 23 * 3600;

        if ($worklist !== null) {
            $rows = $this->select->handle($worklist, $start, $end);

            return $rows === [] ? null : [
                'filename' => 'werklijst.pdf',
                'mime' => 'application/pdf',
                'content' => $this->render->handle($worklist, $rows, $day['display_date']),
            ];
        }

        $files = [];

        foreach (Worklist::query()->orderBy('id')->get() as $candidate) {
            $rows = $this->select->handle($candidate, $start, $end);

            if ($rows === []) {
                continue;
            }

            $name = Export::safeFilename((string) $candidate->title) ?: 'werklijst';
            $files[$this->uniqueName($name, $files)] = $this->render->handle($candidate, $rows, $day['display_date']);
        }

        return $files === [] ? null : [
            'filename' => 'werklijsten.zip',
            'mime' => 'application/zip',
            'content' => $this->zip($files),
        ];
    }

    /** @param  array<string, string>  $files */
    private function uniqueName(string $name, array $files): string
    {
        $candidate = $name.'.pdf';

        for ($copy = 1; array_key_exists($candidate, $files); $copy++) {
            $candidate = "{$name} ({$copy}).pdf";
        }

        return $candidate;
    }

    /** @param  array<string, string>  $files */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'worklists');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Kon het zip-bestand niet aanmaken.');
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }
}
