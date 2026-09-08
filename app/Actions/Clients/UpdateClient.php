<?php

namespace App\Actions\Clients;

use App\Models\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateClient
{
    public function handle(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->update(Arr::except($data, 'categories'));
            $client->categories()->sync($data['categories'] ?? []);

            return $client->fresh('categories');
        });
    }
}