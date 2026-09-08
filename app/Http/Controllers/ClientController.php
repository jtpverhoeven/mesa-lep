<?php

namespace App\Http\Controllers;

use App\Actions\Clients\CreateClient;
use App\Actions\Clients\DeactivateClient;
use App\Actions\Clients\UpdateClient;
use App\Http\Requests\SaveClientRequest;
use App\Models\Client;
use App\Models\ClientCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', [
            'clients' => Client::with('categories:id,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('clients.create', ['categories' => $this->categories()]);
    }

    public function store(SaveClientRequest $request, CreateClient $createClient): RedirectResponse
    {
        $client = $createClient->handle($request->validated());

        return to_route('clients.edit', $client)->with('success', 'Klant "'.$client->name.'" is aangemaakt.');
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', [
            'client' => $client->load('categories:id,name'),
            'categories' => $this->categories(),
        ]);
    }

    public function update(SaveClientRequest $request, Client $client, UpdateClient $updateClient): RedirectResponse
    {
        $client = $updateClient->handle($client, $request->validated());

        return to_route('clients.edit', $client)->with('success', 'Klant "'.$client->name.'" is bijgewerkt.');
    }

    public function destroy(Request $request, Client $client, DeactivateClient $deactivateClient): RedirectResponse|JsonResponse
    {
        $deactivateClient->handle($client);

        if ($request->expectsJson()) {
            return response()->json(status: 204);
        }

        return to_route('clients.index')->with('success', 'Klant is gedeactiveerd.');
    }

    private function categories()
    {
        return ClientCategory::orderBy('name')->get(['id', 'name']);
    }
}