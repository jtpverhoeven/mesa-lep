<?php

namespace App\Console\Commands;

use App\Models\Assay;
use App\Models\AssayField;
use App\Models\AssayType;
use App\Models\AssayTypeField;
use App\Models\ClientCategory;
use App\Models\ClientCategoryAssignment;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\Media;
use App\Models\Matrix;
use App\Models\MatrixContent;
use App\Models\ProjectField;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\SampleProcedureField;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Faker\Generator;
use RuntimeException;
use Throwable;

class ImportLegacyCsvDirectoryCommand extends Command
{
    private ?Generator $faker = null;

    protected $signature = 'legacy:import-csv
                            {directory : Directory containing legacy CSV exports}';

    protected $description = 'Import registered legacy CSV exports while preserving their IDs';

    /**
     * Add new legacy exports here as their Laravel models become available.
     * The order keeps parent records ahead of records that reference them.
     *
     * @var array<string, class-string<Model>>
     */
    private const IMPORTS = [
        'assaytypes.csv' => AssayType::class,
        'assaytypefields.csv' => AssayTypeField::class,
        'assayfields.csv' => AssayField::class,
        'media.csv' => Media::class,
        'assays.csv' => Assay::class,
        'matrix.csv' => Matrix::class,
        'matrixcontent.csv' => MatrixContent::class,
        'clientcategories.csv' => ClientCategory::class,
        'clients.csv' => Client::class,
        'categories_clients.csv' => ClientCategoryAssignment::class,
        'cvars.csv' => Cvar::class,
        'projectfields.csv' => ProjectField::class,
        'samplefields.csv' => SampleField::class,
        'sampleprocedurefields.csv' => SampleProcedureField::class,
        'sampleprocedures.csv' => SampleProcedure::class,
    ];

    public function handle(): int
    {
        $directory = $this->resolveDirectory((string) $this->argument('directory'));

        if (! is_dir($directory) || ! is_readable($directory)) {
            $this->error("CSV directory is not readable: {$directory}");

            return self::FAILURE;
        }

        $this->truncateImportTables();

        $imported = 0;
        $failures = 0;
        $foundFiles = 0;

        foreach (self::IMPORTS as $filename => $modelClass) {
            $path = $directory.DIRECTORY_SEPARATOR.$filename;

            if (! is_file($path)) {
                continue;
            }

            $foundFiles++;
            [$fileImported, $fileFailures] = $this->importFile($path, $modelClass);
            $imported += $fileImported;
            $failures += $fileFailures;
        }

        if ($foundFiles === 0) {
            $this->warn('No registered legacy CSV exports were found.');

            return self::FAILURE;
        }

        $this->info("Imported {$imported} row(s) from {$foundFiles} file(s).");

        if ($failures > 0) {
            $this->error("{$failures} row(s) or file(s) failed to import.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param class-string<Model> $modelClass
     * @return array{int, int}
     */
    private function importFile(string $path, string $modelClass): array
    {
        $filename = basename($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $this->error("{$filename}: unable to open file.");

            return [0, 1];
        }

        try {
            if (fread($handle, 3) !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headers = fgetcsv($handle);

            if ($headers === false || $headers === [] || in_array('', $headers, true)) {
                throw new RuntimeException('header row is missing or contains an empty column name');
            }

            $headers = array_map(static fn ($header) => trim((string) $header), $headers);

            if (count($headers) !== count(array_unique($headers))) {
                throw new RuntimeException('header row contains duplicate column names');
            }

            if (! in_array('id', $headers, true)) {
                throw new RuntimeException('header row must contain an id column');
            }

            $model = new $modelClass;
            $columns = Schema::getColumnListing($model->getTable());
            $unknownColumns = array_diff($headers, $columns);

            if ($unknownColumns !== []) {
                throw new RuntimeException('unknown database columns: '.implode(', ', $unknownColumns));
            }

            $imported = 0;
            $failures = 0;
            $line = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    $this->error("{$filename}: line {$line}: expected ".count($headers).' columns, found '.count($row).'.');
                    $failures++;

                    continue;
                }

                $attributes = array_combine($headers, array_map([$this, 'normalizeValue'], $row));
                $attributes = $this->anonymizeClient($modelClass, $attributes);

                try {
                    DB::transaction(function () use ($modelClass, $attributes): void {
                        $model = new $modelClass;
                        $model->forceFill($attributes);
                        $model->save();
                    });
                    $imported++;
                } catch (Throwable $exception) {
                    $this->error("{$filename}: line {$line}: {$exception->getMessage()}");
                    $failures++;
                }
            }

            $this->line("{$filename}: {$imported} imported, {$failures} failed.");

            return [$imported, $failures];
        } catch (Throwable $exception) {
            $this->error("{$filename}: {$exception->getMessage()}");

            return [0, 1];
        } finally {
            fclose($handle);
        }
    }

    private function normalizeValue(mixed $value): mixed
    {
        return in_array($value, ['NULL', 'NA'], true) ? null : $value;
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function anonymizeClient(string $modelClass, array $attributes): array
    {
        if ($modelClass !== Client::class) {
            return $attributes;
        }

        $faker = $this->faker ??= fake('nl_NL');
        $faker->seed((int) $attributes['id']);

        return array_replace($attributes, [
            'title' => $faker->randomElement(['Dhr.', 'Mevr.']),
            'fname' => $faker->firstName(),
            'mname' => $faker->optional()->lastName(),
            'lname' => $faker->lastName(),
            'street_name' => $faker->streetName(),
            'street_number' => $faker->buildingNumber(),
            'postal_code' => $faker->postcode(),
            'place' => $faker->city(),
            'country' => 'Nederland',
            'telephone' => $faker->phoneNumber(),
            'cellphone' => $faker->phoneNumber(),
            'email' => $faker->safeEmail(),
        ]);
    }

    private function truncateImportTables(): void
    {
        foreach (array_reverse(self::IMPORTS) as $modelClass) {
            $model = new $modelClass;
            $model->newQuery()->truncate();
        }
    }

    private function resolveDirectory(string $directory): string
    {
        if (is_dir($directory)) {
            return $directory;
        }

        return base_path($directory);
    }
}