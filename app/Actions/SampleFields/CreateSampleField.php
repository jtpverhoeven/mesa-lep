<?php

namespace App\Actions\SampleFields;

use App\Models\SampleField;

class CreateSampleField
{
    public function handle(array $data): SampleField
    {
        return SampleField::create($data);
    }
}