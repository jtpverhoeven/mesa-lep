<?php

namespace App\Console\Commands;

use App\Models\AssuranceForm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportLegacyAssuranceFormsCommand extends Command
{
    protected $signature = 'legacy:import-assurance-forms
                            {file=legacy/imports/assuranceforms.csv : Legacy assuranceforms CSV export}
                            {--dry-run : Parse and report without writing}';

    protected $description = 'Import the legacy assuranceforms table without changing its structure';

    public function handle(): int
    {
        try {
            $records = $this->readCsv((string) $this->argument('file'));
            $this->validateTargetCollisions($records);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Forms: '.count($records));

        if ($this->option('dry-run')) {
            $this->info('Dry run complete; no rows written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                AssuranceForm::query()->updateOrCreate(['id' => $record['id']], $record);
            }
        });

        $this->info('Legacy assurance forms imported.');

        return self::SUCCESS;
    }

    /** @return array<int, array{id: int, date: string, data: string, is_complete: int}> */
    private function readCsv(string $path): array
    {
        $absolutePath = str_starts_with($path, '/') ? $path : base_path($path);
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open {$path}.");
        }

        $headers = fgetcsv($handle, escape: '');
        $required = ['id', 'date', 'data', 'is_complete'];

        if (! is_array($headers) || array_diff($required, $headers) !== []) {
            fclose($handle);
            throw new RuntimeException('CSV must contain id,date,data,is_complete headers.');
        }

        $records = [];
        $ids = [];
        $dates = [];
        $line = 1;

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if (count($row) !== count($headers)) {
                fclose($handle);
                throw new RuntimeException("Line {$line}: column count does not match the header.");
            }

            $record = array_combine($headers, $row);
            $id = filter_var($record['id'], FILTER_VALIDATE_INT);
            $date = (string) $record['date'];
            $complete = filter_var($record['is_complete'], FILTER_VALIDATE_INT);

            if ($id === false || $id < 0 || isset($ids[$id])) {
                fclose($handle);
                throw new RuntimeException("Line {$line}: invalid or duplicate id.");
            }

            if (! ctype_digit($date) || isset($dates[$date])) {
                fclose($handle);
                throw new RuntimeException("Line {$line}: invalid or duplicate date.");
            }

            json_decode((string) $record['data'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                fclose($handle);
                throw new RuntimeException("Line {$line}: malformed JSON data.");
            }

            if ($complete === false) {
                fclose($handle);
                throw new RuntimeException("Line {$line}: is_complete must be an integer.");
            }

            $ids[$id] = true;
            $dates[$date] = true;
            $records[] = ['id' => $id, 'date' => $date, 'data' => (string) $record['data'], 'is_complete' => $complete];
        }

        fclose($handle);

        return $records;
    }

    /** @param array<int, array{id: int, date: string, data: string, is_complete: int}> $records */
    private function validateTargetCollisions(array $records): void
    {
        $ids = collect($records)->pluck('id');
        $dates = collect($records)->pluck('date');
        $existingById = AssuranceForm::query()->whereIn('id', $ids)->pluck('date', 'id');
        $existingByDate = AssuranceForm::query()->whereIn('date', $dates)->pluck('id', 'date');

        foreach ($records as $record) {
            if ($existingById->has($record['id']) && (string) $existingById[$record['id']] !== $record['date']) {
                throw new RuntimeException("Form {$record['id']} already represents another date.");
            }

            if ($existingByDate->has($record['date']) && (int) $existingByDate[$record['date']] !== $record['id']) {
                throw new RuntimeException("Date {$record['date']} already belongs to another form.");
            }
        }
    }
}
