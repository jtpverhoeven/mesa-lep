<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmationTables\DeleteConfirmationTable;
use App\Actions\ConfirmationTables\SaveConfirmationTable;
use App\Actions\Media\StoreEditorImages;
use App\Http\Requests\SaveConfirmationTableRequest;
use App\Models\ConfirmationTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfirmationTableController extends Controller
{
    public function index(): View
    {
        return view('confirmation-tables.index', [
            'tables' => ConfirmationTable::query()
                ->withCount(['assays' => fn ($query) => $query->where('active', 1)])
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('confirmation-tables.edit', ['table' => new ConfirmationTable(['name' => '', 'html' => ''])]);
    }

    public function edit(ConfirmationTable $confirmationTable): View
    {
        return view('confirmation-tables.edit', ['table' => $confirmationTable]);
    }

    public function store(SaveConfirmationTableRequest $request, SaveConfirmationTable $save): RedirectResponse
    {
        $table = $save->handle(new ConfirmationTable, $request->validated());

        return to_route('confirmation-tables.edit', $table)->with('success', 'Bevestigingstabel aangemaakt.');
    }

    public function update(SaveConfirmationTableRequest $request, ConfirmationTable $confirmationTable, SaveConfirmationTable $save): RedirectResponse
    {
        $save->handle($confirmationTable, $request->validated());

        return to_route('confirmation-tables.edit', $confirmationTable)->with('success', 'Bevestigingstabel opgeslagen.');
    }

    public function destroy(ConfirmationTable $confirmationTable, DeleteConfirmationTable $delete): RedirectResponse
    {
        $delete->handle($confirmationTable);

        return to_route('confirmation-tables.index')->with('success', 'Bevestigingstabel verwijderd.');
    }

    public function image(Request $request, ConfirmationTable $confirmationTable, string $image): StreamedResponse
    {
        abort_unless($request->user()->canAny(['samples.view', 'media.manage']), 403);

        $path = StoreEditorImages::directory($confirmationTable).'/'.$image;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
