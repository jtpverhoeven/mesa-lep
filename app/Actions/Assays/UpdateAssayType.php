<?php

namespace App\Actions\Assays;

use App\Models\AssayType;

class UpdateAssayType
{
    public function handle(AssayType $assayType, array $data): AssayType
    {
        $assayType->update($data);

        return $assayType->fresh();
    }
}