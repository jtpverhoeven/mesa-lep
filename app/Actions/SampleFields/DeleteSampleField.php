<?php

namespace App\Actions\SampleFields;

use App\Models\SampleField;

class DeleteSampleField
{
    public function handle(SampleField $field): bool
    {
        return $field->delete();
    }
}