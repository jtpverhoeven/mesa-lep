<?php

namespace App\Actions\Matrix;

use App\Models\Matrix;
use Illuminate\Support\Facades\DB;

class UpdateMatrix
{
    public function handle(Matrix $matrix, array $data): Matrix
    {
        return DB::transaction(function () use ($matrix, $data): Matrix {
            $matrix->update(['name' => $data['name']]);

            return $matrix->fresh();
        });
    }
}