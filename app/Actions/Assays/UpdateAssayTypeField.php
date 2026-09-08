<?php

namespace App\Actions\Assays;

use App\Models\AssayTypeField;

class UpdateAssayTypeField
{
    public function handle(AssayTypeField $field, array $data): AssayTypeField
    {
        $field->update($data);

        return $field->fresh();
    }
}