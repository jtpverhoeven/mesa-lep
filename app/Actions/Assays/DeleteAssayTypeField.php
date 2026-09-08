<?php

namespace App\Actions\Assays;

use App\Models\AssayTypeField;

class DeleteAssayTypeField
{
    public function handle(AssayTypeField $field): bool
    {
        return (bool) $field->delete();
    }
}