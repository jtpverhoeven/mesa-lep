<?php

namespace App\Actions\Reports;

use App\Models\Export;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetReportTransaction
{
    /** @return Collection<int, Export> */
    public function handle(int $transaction): Collection
    {
        $exports = Export::query()->inTransaction($transaction)->get();

        if ($exports->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(Export::class, [$transaction]);
        }

        return $exports;
    }
}
