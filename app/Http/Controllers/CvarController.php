<?php

namespace App\Http\Controllers;

use App\Actions\Cvars\SetCvar;
use App\Http\Requests\UpdateCvarRequest;
use App\Models\Cvar;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CvarController extends Controller
{
    public function index(): View
    {
        return view('cvars.index', [
            'cvars' => Cvar::orderBy('cvar')->get(['id', 'cvar', 'value', 'default', 'description']),
        ]);
    }

    public function update(UpdateCvarRequest $request, Cvar $cvar, SetCvar $setCvar): JsonResponse
    {
        $cvar = $setCvar->handle($cvar, $request->validated('value') ?? '');

        return response()->json([
            'id' => $cvar->id,
            'value' => $cvar->value,
        ]);
    }
}