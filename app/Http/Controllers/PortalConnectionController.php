<?php

namespace App\Http\Controllers;

use App\Actions\ClientPortal\UpdatePortalSettings;
use App\ClientPortal\ClientPortalService;
use App\ClientPortal\PortalSettings;
use App\Http\Requests\UpdatePortalConnectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class PortalConnectionController extends Controller
{
    public function index(PortalSettings $settings): View
    {
        return view('portal.connection', [
            'settings' => $settings->connection(),
            'portalUrl' => config('services.glass.base_url'),
        ]);
    }

    public function update(
        UpdatePortalConnectionRequest $request,
        UpdatePortalSettings $updatePortalSettings,
    ): JsonResponse {
        $updatePortalSettings->handle($request->validated());

        return response()->json(['message' => 'Verbindingsinstellingen opgeslagen.']);
    }

    public function test(ClientPortalService $clientPortal): JsonResponse
    {
        try {
            $response = $clientPortal->connectionTest();
        } catch (Throwable) {
            return response()->json([
                'message' => 'De client portal kon niet worden bereikt.',
            ], 502);
        }

        if ($response->successful()) {
            return response()->json([
                'message' => $response->json('message') ?? 'Verbinding met de client portal is geslaagd.',
                'status' => $response->status(),
            ]);
        }

        return response()->json([
            'message' => $response->json('message') ?? 'De client portal weigerde de verbinding.',
            'status' => $response->status(),
        ], 502);
    }
}
