<?php

namespace App\Actions\ProjectFields;

use App\Models\ProjectField;

class UpdateProjectField
{
    public function handle(ProjectField $field, array $data): ProjectField
    {
        $field->update($data);

        return $field->fresh();
    }
}