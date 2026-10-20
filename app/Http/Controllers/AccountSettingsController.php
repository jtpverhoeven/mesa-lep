<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Profiles\DeleteAccountImage;
use App\Actions\Profiles\SaveAccountImage;
use App\Actions\Profiles\UpdateAccountAlias;
use App\Actions\Profiles\UpdateAccountProfile;
use App\Http\Requests\UpdateAccountAliasRequest;
use App\Http\Requests\UpdateAccountProfileRequest;
use App\Http\Requests\UploadAccountImageRequest;
use App\Support\AccountImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('profile');
        [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');

        return view('account.settings', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'alias' => $user->alias,
                'profile' => $user->profile?->only(['title', 'first_name', 'last_name', 'gender', 'job_title', 'phone_number'])
                    ?? ['first_name' => $firstName, 'last_name' => $lastName],
                'images' => collect(['avatar', 'nl', 'en'])->mapWithKeys(fn (string $kind): array => [$kind => AccountImages::url($user, $kind)])->all(),
            ],
        ]);
    }

    public function updateProfile(UpdateAccountProfileRequest $request, UpdateAccountProfile $update): JsonResponse
    {
        $update->handle($request->user(), $request->validated());

        return response()->json(['message' => 'Persoonlijke gegevens opgeslagen.', 'name' => $request->user()->name]);
    }

    public function updateAlias(UpdateAccountAliasRequest $request, UpdateAccountAlias $update): JsonResponse
    {
        $update->handle($request->user(), $request->validated('alias'));

        return response()->json(['message' => 'Systeemalias opgeslagen.', 'alias' => $request->user()->alias]);
    }

    public function updatePassword(Request $request, UpdateUserPassword $update): JsonResponse
    {
        $update->update($request->user(), $request->only('current_password', 'password', 'password_confirmation'));

        return response()->json(['message' => 'Wachtwoord gewijzigd.']);
    }

    public function storeImage(UploadAccountImageRequest $request, string $kind, SaveAccountImage $save): JsonResponse
    {
        $save->handle($request->user(), $kind, $request->file('image'));

        return response()->json(['message' => 'Afbeelding opgeslagen.', 'url' => AccountImages::url($request->user()->refresh(), $kind)]);
    }

    public function showImage(Request $request, string $kind): StreamedResponse
    {
        $path = AccountImages::path($request->user(), $kind);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyImage(Request $request, string $kind, DeleteAccountImage $delete): JsonResponse
    {
        $delete->handle($request->user(), $kind);

        return response()->json(['message' => 'Afbeelding verwijderd.']);
    }
}
