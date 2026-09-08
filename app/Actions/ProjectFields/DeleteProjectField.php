<?php

namespace App\Actions\ProjectFields;

use App\Models\ProjectField;

class DeleteProjectField
{
    public function handle(ProjectField $field): bool
    {
        return $field->delete();
    }
}