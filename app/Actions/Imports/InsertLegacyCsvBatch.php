<?php

namespace App\Actions\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use RuntimeException;

class InsertLegacyCsvBatch
{
    /**
     * Rows are prepared with Eloquent's fillForInsert before batching so retries
     * reuse the stored values without applying model mutators a second time.
     *
     * @param  list<array{line: int, attributes: array<string, mixed>}>  $rows
     * @return array{imported: int, errors: array<int, string>, retried: bool}
     */
    public function handle(Model $model, array $rows): array
    {
        $connection = $model->getConnection();

        try {
            $connection->transaction(fn () => $this->insert($model, array_column($rows, 'attributes')));

            return ['imported' => count($rows), 'errors' => [], 'retried' => false];
        } catch (QueryException $exception) {
            if (count($rows) === 1) {
                return ['imported' => 0, 'errors' => [$rows[0]['line'] => $exception->getMessage()], 'retried' => false];
            }
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                $connection->transaction(fn () => $this->insert($model, [$row['attributes']]));
                $imported++;
            } catch (QueryException $exception) {
                $errors[$row['line']] = $exception->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors, 'retried' => true];
    }

    /**
     * @param  list<array<string, mixed>>  $attributes
     */
    private function insert(Model $model, array $attributes): void
    {
        if (! $model->newModelQuery()->insert($attributes)) {
            throw new RuntimeException('CSV batch insert did not complete.');
        }
    }
}
