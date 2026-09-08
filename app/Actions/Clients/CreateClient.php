<?php

namespace App\Actions\Clients;

use App\Models\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateClient
{
    public function handle(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $client = Client::create(Arr::except($data, 'categories'));
            $client->categories()->sync($data['categories'] ?? []);

            return $client->load('categories');
        });
    }
}