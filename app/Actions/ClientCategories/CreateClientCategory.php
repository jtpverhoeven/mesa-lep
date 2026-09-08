<?php

namespace App\Actions\ClientCategories;

use App\Models\ClientCategory;

class CreateClientCategory
{
    public function handle(array $data): ClientCategory
    {
        return ClientCategory::create(['name' => $data['name']]);
    }
}