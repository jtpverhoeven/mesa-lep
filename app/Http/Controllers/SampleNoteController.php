<?php

namespace App\Http\Controllers;

use App\Actions\Notes\AddSampleNote;
use App\Actions\Notes\DeleteSampleNote;
use App\Actions\Notes\ListSampleNotes;
use App\Enums\NoteKind;
use App\Http\Requests\StoreSampleNoteRequest;
use App\Models\Note;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SampleNoteController extends Controller
{
    public function index(Request $request, Sample $sample, ListSampleNotes $list): JsonResponse
    {
        abort_unless($request->user()?->canAny(['samples.view', 'projects.view']), 403);

        return response()->json(['data' => $list->handle($sample)]);
    }

    public function store(StoreSampleNoteRequest $request, Sample $sample, AddSampleNote $add, ListSampleNotes $list): JsonResponse
    {
        $add->handle($sample, $request->validated('body'), $request->user()->id, $request->validated('mentions', []));

        return response()->json(['data' => $list->handle($sample)], 201);
    }

    public function destroy(Request $request, Sample $sample, Note $note, DeleteSampleNote $delete, ListSampleNotes $list): JsonResponse
    {
        abort_unless($note->kind === NoteKind::Internal, 404);
        abort_unless($request->user()->can('samples.notes.update') && $note->user_id === $request->user()->id, 403);

        $delete->handle($sample, $note);

        return response()->json(['data' => $list->handle($sample)]);
    }
}
