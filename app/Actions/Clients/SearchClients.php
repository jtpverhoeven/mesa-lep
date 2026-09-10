<?php

namespace App\Actions\Clients;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class SearchClients
{
    public function handle(string $query): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        return Client::query()
            ->where('active', 1)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'ilike', '%'.$query.'%')
                    ->orWhere('reference', 'ilike', '%'.$query.'%');
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'reference']);
    }
}