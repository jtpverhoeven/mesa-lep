<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccountImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAvatarController extends Controller
{
    public function __invoke(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->canAny(['samples.view', 'projects.view']), 403);

        $path = AccountImages::path($user->loadMissing('profile'), 'avatar');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
