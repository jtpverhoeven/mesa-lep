<?php

namespace App\Actions\Matrix;

use App\Models\Matrix;
use Illuminate\Support\Facades\DB;

class DeleteMatrix
{
    public function handle(Matrix $matrix): Matrix
    {
        return DB::transaction(function () use ($matrix): Matrix {
            $matrix->contents()->delete();
            $matrix->update(['active' => 0]);

            return $matrix->fresh();
        });
    }
}