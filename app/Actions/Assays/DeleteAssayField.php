<?php

namespace App\Actions\Assays;

use App\Models\AssayField;
use Illuminate\Support\Facades\DB;

class DeleteAssayField
{
    public function handle(AssayField $assayField): AssayField
    {
        return DB::transaction(function () use ($assayField): AssayField {
            $assayField->update(['active' => 0]);

            return $assayField->fresh();
        });
    }
}