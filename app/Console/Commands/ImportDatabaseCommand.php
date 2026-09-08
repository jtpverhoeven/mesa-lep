<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportDatabaseCommand extends Command
{
    protected $signature = 'db:import
                            {filename : The path to the SQL file}';

    protected $description = 'Import an SQL file into the current database';

    public function handle(): int
    {
        $filename = (string) $this->argument('filename');
        $path = $this->resolvePath($filename);

        if (! is_file($path)) {
            $this->error("SQL file not found: {$filename}");

            return self::FAILURE;
        }

        if (! is_readable($path)) {
            $this->error("SQL file is not readable: {$filename}");

            return self::FAILURE;
        }

        $sql = file_get_contents($path);

        if ($sql === false) {
            $this->error("Unable to read SQL file: {$filename}");

            return self::FAILURE;
        }

        if (trim($sql) === '') {
            $this->error("SQL file is empty: {$filename}");

            return self::FAILURE;
        }

        try {
            DB::connection()->unprepared($sql);
        } catch (Throwable $exception) {
            $this->error('Database import failed.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported SQL file: {$path}");

        return self::SUCCESS;
    }

    private function resolvePath(string $filename): string
    {
        if (is_file($filename)) {
            return $filename;
        }

        return base_path($filename);
    }
}