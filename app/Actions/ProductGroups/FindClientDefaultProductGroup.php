<?php

namespace App\Actions\ProductGroups;

use App\Models\ProductGroup;

class FindClientDefaultProductGroup
{
    public function handle(int $clientId): ?ProductGroup
    {
        return ProductGroup::query()
            ->where('client_id', $clientId)
            ->where('default', 1)
            ->first();
    }
}
