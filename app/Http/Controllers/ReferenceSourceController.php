<?php

namespace App\Http\Controllers;

use App\Actions\ReferenceSources\CreateReferenceSource;
use App\Actions\ReferenceSources\DeleteReferenceSource;
use App\Actions\ReferenceSources\UpdateReferenceSource;
use App\Http\Requests\StoreReferenceSourceRequest;
use App\Http\Requests\UpdateReferenceSourceRequest;
use App\Models\Client;
use App\Models\ReferenceSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferenceSourceController extends Controller
{
    public function index(): View
    {
        return view('reference-sources.index', [
            'referenceSources' => ReferenceSource::with('clientRecord:id,name')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('reference-sources.create', ['clients' => $this->clients()]);
    }

    public function store(StoreReferenceSourceRequest $request, CreateReferenceSource $createReferenceSource): RedirectResponse
    {
        $referenceSource = $createReferenceSource->handle($request->validated());

        return to_route('reference-sources.index')->with('success', 'Referentiebron "'.$referenceSource->localizedName().'" is aangemaakt.');
    }

    public function edit(ReferenceSource $referenceSource): View
    {
        return view('reference-sources.edit', [
            'referenceSource' => $referenceSource,
            'clients' => $this->clients($referenceSource),
        ]);
    }

    public function update(
        UpdateReferenceSourceRequest $request,
        ReferenceSource $referenceSource,
        UpdateReferenceSource $updateReferenceSource,
    ): RedirectResponse {
        $referenceSource = $updateReferenceSource->handle($referenceSource, $request->validated());

        return to_route('reference-sources.edit', $referenceSource)->with('success', 'Referentiebron "'.$referenceSource->localizedName().'" is bijgewerkt.');
    }

    public function destroy(ReferenceSource $referenceSource, DeleteReferenceSource $deleteReferenceSource): RedirectResponse
    {
        $name = $referenceSource->localizedName();
        $deleteReferenceSource->handle($referenceSource);

        return to_route('reference-sources.index')->with('success', 'Referentiebron "'.$name.'" is verwijderd.');
    }

    private function clients(?ReferenceSource $referenceSource = null)
    {
        return Client::query()
            ->where('active', 1)
            ->when($referenceSource?->client, fn ($query) => $query->orWhereKey($referenceSource->client))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}