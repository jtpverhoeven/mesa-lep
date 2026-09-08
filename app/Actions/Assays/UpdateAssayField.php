<?php

namespace App\Actions\Assays;

use App\Models\AssayField;

class UpdateAssayField
{
    public function handle(AssayField $assayField, array $data): AssayField
    {
        $assayField->update([
            'name' => $data['name'],
            'standard_value' => $data['has_default_value'] ? ($data['standard_value'] ?? null) : null,
            'position' => $data['position'],
        ]);

        return $assayField->fresh();
    }
}