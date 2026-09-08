<?php

namespace App\Actions\ProjectFields;

use App\Models\ProjectField;

class CreateProjectField
{
    public function handle(array $data): ProjectField
    {
        return ProjectField::create($data);
    }
}