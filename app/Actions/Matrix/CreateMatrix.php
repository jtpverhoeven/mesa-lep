<?php

namespace App\Actions\Matrix;

use App\Models\Matrix;
use Illuminate\Support\Facades\DB;

class CreateMatrix
{
    public function handle(array $data): Matrix
    {
        return DB::transaction(fn (): Matrix => Matrix::create([
            'name' => $data['name'],
        ]));
    }
}