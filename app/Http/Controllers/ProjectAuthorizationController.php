<?php

namespace App\Http\Controllers;

use App\Actions\Projects\AuthorizeProject;
use App\Actions\Projects\DeauthorizeProject;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectAuthorizationController extends Controller
{
    public function store(Request $request, Project $project, AuthorizeProject $authorize): JsonResponse
    {
        $request->validate([
            'quiet' => ['sometimes', 'boolean'],
            'override_incomplete' => ['sometimes', 'boolean'],
        ]);

        $result = $authorize->handle(
            $project,
            (int) $request->user()->getAuthIdentifier(),
            $request->boolean('quiet'),
            $request->boolean('override_incomplete'),
        );

        return response()->json(['data' => $result]);
    }

    public function destroy(Request $request, Project $project, DeauthorizeProject $deauthorize): JsonResponse
    {
        $data = $request->validate([
            'origin' => [
                'required',
                Rule::in([
                    'Intern (oorzaak/fout bij MAZ)',
                    'Extern (oorzaak/fout bij klant)',
                ]),
            ],
            'reason' => ['required', 'string'],
        ]);
        $data['reason'] = trim($data['reason']);

        if ($data['reason'] === '') {
            throw ValidationException::withMessages(['reason' => 'Voer een deautorisatiereden in.']);
        }

        $result = $deauthorize->handle(
            $project,
            (int) $request->user()->getAuthIdentifier(),
            $data['origin'],
            $data['reason'],
        );

        return response()->json(['data' => $result]);
    }
}
