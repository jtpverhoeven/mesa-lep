<?php

namespace App\Actions\Assays;

use App\Models\AssayType;
use App\Models\AssayTypeField;

class CreateAssayTypeField
{
    public function handle(AssayType $assayType, array $data): AssayTypeField
    {
        return $assayType->fields()->create([
            'name' => $data['name'],
            'alias' => $data['alias'],
            'type' => $data['type'],
            'pos' => $data['pos'],
            'endresults_driver' => $data['endresults_driver'] ?? 1,
        ]);
    }
}