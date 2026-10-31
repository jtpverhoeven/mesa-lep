<?php

namespace App\Http\Controllers;

use App\Actions\Keyrings\AcquireKeyring;
use App\Actions\Keyrings\ReleaseKeyring;
use App\Actions\Keyrings\ReleaseKeyringById;
use App\Actions\Keyrings\StealKeyring;
use App\Keyrings\KeyringType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KeyringController extends Controller
{
    public function store(Request $request, string $type, int $typeId, AcquireKeyring $acquire): JsonResponse
    {
        $keyringType = $this->authorizedType($request, $type, $typeId);

        return response()->json(['data' => $acquire->handle($keyringType, $typeId, $request->user())]);
    }

    public function steal(Request $request, string $type, int $typeId, StealKeyring $steal): JsonResponse
    {
        $keyringType = $this->authorizedType($request, $type, $typeId);

        return response()->json(['data' => $steal->handle($keyringType, $typeId, $request->user())]);
    }

    public function destroy(Request $request, string $type, int $typeId, ReleaseKeyring $release): Response
    {
        $release->handle(KeyringType::fromSlug($type), $typeId, $request->user());

        return response()->noContent();
    }

    public function release(Request $request, int $keyring, ReleaseKeyringById $release): Response
    {
        $release->handle($keyring, $request->user());

        return response()->noContent();
    }

    private function authorizedType(Request $request, string $type, int $typeId): KeyringType
    {
        $keyringType = KeyringType::fromSlug($type);
        abort_unless($keyringType->isAccessibleBy($request->user(), $typeId), 403);

        return $keyringType;
    }
}
