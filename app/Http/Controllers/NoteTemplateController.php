<?php

namespace App\Http\Controllers;

use App\Actions\Notes\ListNoteTemplates;
use App\Actions\Notes\SaveNoteTemplate;
use App\Http\Requests\SaveNoteTemplateRequest;
use App\Models\NoteTemplate;
use Illuminate\Http\JsonResponse;

class NoteTemplateController extends Controller
{
    public function index(ListNoteTemplates $list): JsonResponse
    {
        return response()->json(['data' => $list->handle()]);
    }

    public function store(SaveNoteTemplateRequest $request, SaveNoteTemplate $save, ListNoteTemplates $list): JsonResponse
    {
        $save->handle($request->validated('name'), $request->validated('content'));

        return response()->json(['data' => $list->handle()], 201);
    }

    public function destroy(NoteTemplate $noteTemplate, ListNoteTemplates $list): JsonResponse
    {
        $noteTemplate->delete();

        return response()->json(['data' => $list->handle()]);
    }
}
