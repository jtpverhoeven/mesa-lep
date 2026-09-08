<?php

namespace App\Actions\Assays;

use App\Models\AssayField;

class CreateAssayField
{
    public function handle(array $data): AssayField
    {
        return AssayField::create([
            'name' => $data['name'],
            'standard_value' => $data['has_default_value'] ? ($data['standard_value'] ?? null) : null,
            'position' => $data['position'],
        ]);
    }
}