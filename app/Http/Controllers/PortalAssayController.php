<?php

namespace App\Http\Controllers;

use App\Actions\PortalAssays\CreatePortalAssay;
use App\Actions\PortalAssays\SyncPortalAssayClients;
use App\Actions\PortalAssays\TogglePortalAssayAssay;
use App\Actions\PortalAssays\UpdatePortalAssay;
use App\Http\Requests\CopyPortalAssayClientsRequest;
use App\Http\Requests\StorePortalAssayRequest;
use App\Http\Requests\SyncPortalAssayClientsRequest;
use App\Http\Requests\TogglePortalAssayAssayRequest;
use App\Http\Requests\UpdatePortalAssayRequest;
use App\Models\Assay;
use App\Models\Client;
use App\Models\PortalAssay;
use App\Models\PortalAssayContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortalAssayController extends Controller
{
    public function index(): View
    {
        $portalAssays = PortalAssay::query()
            ->where('active', 1)
            ->withCount(['assays', 'clients'])
            ->orderBy('common_name')
            ->get()
            ->map(fn (PortalAssay $portalAssay): array => [
                'id' => $portalAssay->id,
                'common_name' => $portalAssay->common_name,
                'common_name_en' => $portalAssay->common_name_en,
                'selectable' => (bool) $portalAssay->selectable,
                'alertable' => (bool) $portalAssay->alertable,
                'border_reaction' => (bool) $portalAssay->border_reaction,
                'assays_count' => $portalAssay->assays_count,
                'clients_count' => $portalAssay->clients_count,
            ])->values();

        return view('portal-assays.index', compact('portalAssays'));
    }

    public function create(): View
    {
        return view('portal-assays.create');
    }

    public function store(StorePortalAssayRequest $request, CreatePortalAssay $createPortalAssay): RedirectResponse
    {
        $portalAssay = $createPortalAssay->handle($request->validated());

        return to_route('portal-assays.edit', $portalAssay)
            ->with('success', 'Portaalanalyse "'.$portalAssay->common_name.'" is aangemaakt.');
    }

    public function edit(PortalAssay $portalAssay): View
    {
        $associatedAssayIds = $portalAssay->assayContents()->pluck('assay_id')->map(fn (int $id): string => (string) $id)->all();
        $assignedElsewhereIds = PortalAssayContent::query()
            ->where('common_id', '!=', $portalAssay->id)
            ->pluck('assay_id')
            ->flip();
        $assays = Assay::query()
            ->orderBy('name')
            ->get(['id', 'name', 'original_id', 'active'])
            ->map(fn (Assay $assay): array => [
                'id' => $assay->id,
                'name' => $assay->name,
                'original_id' => $assay->original_id,
                'active' => (bool) $assay->active,
                'assigned_elsewhere' => $assignedElsewhereIds->has($assay->id),
            ])->values();

        return view('portal-assays.edit', compact('portalAssay', 'assays', 'associatedAssayIds'));
    }

    public function update(
        UpdatePortalAssayRequest $request,
        PortalAssay $portalAssay,
        UpdatePortalAssay $updatePortalAssay,
    ): RedirectResponse {
        $portalAssay = $updatePortalAssay->handle($portalAssay, $request->validated());

        return to_route('portal-assays.edit', $portalAssay)
            ->with('success', 'Portaalanalyse "'.$portalAssay->common_name.'" is bijgewerkt.');
    }

    public function toggleAssay(
        TogglePortalAssayAssayRequest $request,
        PortalAssay $portalAssay,
        TogglePortalAssayAssay $togglePortalAssayAssay,
    ): JsonResponse {
        $data = $request->validated();
        $assay = Assay::findOrFail($data['assay_id']);
        $attached = $togglePortalAssayAssay->handle($portalAssay, $assay, $data['attached']);

        return response()->json([
            'assay_id' => $assay->id,
            'attached' => $attached,
        ]);
    }

    public function clients(PortalAssay $portalAssay): View
    {
        $selectedClientIds = $portalAssay->clients()->pluck('clients.id')->map(fn (int $id): string => (string) $id)->all();
        $clients = Client::query()
            ->with('categories:id,name')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'reference'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'reference' => $client->reference,
                'categories' => $client->categories->pluck('name')->filter()->join(', '),
            ])->values();
        $copySources = PortalAssay::query()
            ->where('active', 1)
            ->whereKeyNot($portalAssay)
            ->orderBy('common_name')
            ->get(['id', 'common_name']);

        return view('portal-assays.clients', compact('portalAssay', 'clients', 'selectedClientIds', 'copySources'));
    }

    public function updateClients(
        SyncPortalAssayClientsRequest $request,
        PortalAssay $portalAssay,
        SyncPortalAssayClients $syncPortalAssayClients,
    ): RedirectResponse {
        $data = $request->validated();
        $syncPortalAssayClients->handle(
            $portalAssay,
            $data['client_ids'] ?? [],
            $data['replace_all'],
        );

        return to_route('portal-assays.clients', $portalAssay)
            ->with('success', 'Klantbeschikbaarheid is bijgewerkt.');
    }

    public function copyClients(
        CopyPortalAssayClientsRequest $request,
        PortalAssay $portalAssay,
        SyncPortalAssayClients $syncPortalAssayClients,
    ): RedirectResponse {
        $source = PortalAssay::findOrFail($request->integer('copy_from'));
        $syncPortalAssayClients->replace($portalAssay, $source->clients()->pluck('clients.id')->all());

        return to_route('portal-assays.clients', $portalAssay)
            ->with('success', 'Klantbeschikbaarheid is gekopieerd uit "'.$source->common_name.'".');
    }
}
