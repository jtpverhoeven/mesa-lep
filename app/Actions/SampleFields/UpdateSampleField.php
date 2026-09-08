<?php

namespace App\Actions\SampleFields;

use App\Models\SampleField;

class UpdateSampleField
{
    public function handle(SampleField $field, array $data): SampleField
    {
        $field->update($data);

        return $field->fresh();
    }
}