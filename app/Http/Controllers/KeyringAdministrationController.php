<?php

namespace App\Http\Controllers;

use App\Actions\Keyrings\ForceReleaseKeyring;
use App\Models\Keyring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class KeyringAdministrationController extends Controller
{
    public function index(): View
    {
        return view('keyrings.index', [
            'dataUrl' => route('keyrings.admin.data'),
            'destroyUrl' => route('keyrings.admin.destroy', ['keyring' => '__KEYRING__']),
            'csrf' => csrf_token(),
        ]);
    }

    public function data(): JsonResponse
    {
        $keyrings = Keyring::query()
            ->with('holder:id,name')
            ->orderBy('type')
            ->orderBy('type_id')
            ->get(['id', 'type', 'type_id', 'issued_to', 'expires'])
            ->map(fn (Keyring $keyring): array => [
                'id' => $keyring->id,
                'type' => $keyring->type->slug(),
                'type_id' => $keyring->type_id,
                'holder_id' => $keyring->issued_to,
                'holder_name' => $keyring->holder?->name,
                'expires_at' => $keyring->expiresAt()->toIso8601String(),
                'expired' => $keyring->isExpired(),
            ])
            ->values();

        return response()->json(['data' => $keyrings]);
    }

    public function destroy(Request $request, Keyring $keyring, ForceReleaseKeyring $forceRelease): Response
    {
        $forceRelease->handle($keyring, $request->user());

        return response()->noContent();
    }
}
