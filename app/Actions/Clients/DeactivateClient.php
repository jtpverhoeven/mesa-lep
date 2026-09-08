<?php

namespace App\Actions\Clients;

use App\Models\Client;

class DeactivateClient
{
    public function handle(Client $client): Client
    {
        $client->update(['active' => 0]);

        return $client->fresh();
    }
}