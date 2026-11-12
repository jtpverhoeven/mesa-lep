<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccountImages;
use Illuminate\Http\JsonResponse;

class MentionableUserController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $users = User::query()->where('enabled', true)->with('profile:id,user_id,avatar_path')->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $users->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => AccountImages::userAvatarUrl($user),
        ])->all()]);
    }
}
