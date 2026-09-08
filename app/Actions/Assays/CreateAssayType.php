<?php

namespace App\Actions\Assays;

use App\Models\AssayType;
use App\Models\User;

class CreateAssayType
{
    public function handle(array $data, User $user): AssayType
    {
        return AssayType::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'added_by' => $user->id,
            'added_date' => (string) now()->timestamp,
        ]);
    }
}
