<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ImportCsvCommand extends Command
{
    protected $signature = 'db:import-csv
                            {filename : The path to the CSV file}
                            {model : The Eloquent model class, for example Media or App\\Models\\Media}';

    protected $description = 'Import a CSV file as new Eloquent models';

    public function handle(): int
    {
        $filename = (string) $this->argument('filename');
        $path = $this->resolvePath($filename);

        if (! is_file($path)) {
            $this->error("CSV file not found: {$filename}");

            return self::FAILURE;
        }

        if (! is_readable($path)) {
            $this->error("CSV file is not readable: {$filename}");

            return self::FAILURE;
        }

        $modelClass = $this->resolveModelClass((string) $this->argument('model'));

        if (! class_exists($modelClass) || ! is_a($modelClass, Model::class, true)) {
            $this->error("Eloquent model not found: {$this->argument('model')}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $this->error("Unable to read CSV file: {$filename}");

            return self::FAILURE;
        }

        $headers = fgetcsv($handle);

        if ($headers === false || $headers === [] || count(array_filter($headers, fn ($header) => trim((string) $header) !== '')) !== count($headers)) {
            fclose($handle);
            $this->error('CSV file must start with a non-empty header row.');

            return self::FAILURE;
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        if (count($headers) !== count(array_unique($headers))) {
            fclose($handle);
            $this->error('CSV header names must be unique.');

            return self::FAILURE;
        }

        $imported = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    throw new \RuntimeException('A CSV row has a different number of columns than the header row.');
                }

                $modelClass::create(array_combine($headers, $row));
                $imported++;
            }
        } catch (Throwable $exception) {
            $this->error('CSV import failed.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        } finally {
            fclose($handle);
        }

        $this->info("Imported {$imported} models from CSV file: {$path}");

        return self::SUCCESS;
    }

    private function resolvePath(string $filename): string
    {
        if (is_file($filename)) {
            return $filename;
        }

        return base_path($filename);
    }

    private function resolveModelClass(string $model): string
    {
        if (class_exists($model)) {
            return $model;
        }

        return 'App\\Models\\'.$model;
    }
}