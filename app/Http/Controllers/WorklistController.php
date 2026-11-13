<?php

namespace App\Http\Controllers;

use App\Actions\Media\StoreEditorImages;
use App\Actions\Worklists\DeleteWorklist;
use App\Actions\Worklists\SaveWorklist;
use App\Http\Requests\SaveWorklistRequest;
use App\Models\Assay;
use App\Models\Worklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorklistController extends Controller
{
    public function index(): View
    {
        return view('worklists.index', [
            'worklists' => Worklist::query()->orderBy('title')->orderBy('id')->get(['id', 'title', 'subtitle']),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Worklist(['no_columns' => 1, 'orientation' => 'P', 'analyses' => [], 'columns' => [], 'colum_groups' => [], 'extra_rows' => []]));
    }

    public function store(SaveWorklistRequest $request, SaveWorklist $save): RedirectResponse
    {
        $worklist = $save->handle(new Worklist, $request->validated());

        return to_route('worklists.edit', $worklist)->with('success', 'Werklijst aangemaakt.');
    }

    public function edit(Worklist $worklist): View
    {
        return $this->form($worklist);
    }

    public function update(SaveWorklistRequest $request, Worklist $worklist, SaveWorklist $save): RedirectResponse
    {
        $save->handle($worklist, $request->validated());

        return to_route('worklists.edit', $worklist)->with('success', 'Werklijst opgeslagen.');
    }

    public function destroy(Worklist $worklist, DeleteWorklist $delete): RedirectResponse
    {
        $delete->handle($worklist);

        return to_route('worklists.index')->with('success', 'Werklijst verwijderd.');
    }

    public function image(Request $request, Worklist $worklist, string $image): StreamedResponse
    {
        abort_unless($request->user()->canAny(['worksheets.manage', 'lists.view']), 403);

        $path = StoreEditorImages::directory($worklist).'/'.$image;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function form(Worklist $worklist): View
    {
        // Lists store the revision that was picked; the newest revision of the same analysis stands in for it.
        $assays = Assay::query()
            ->whereIn('id', Assay::query()->selectRaw('max(id)')->groupBy('original_id'))
            ->orderBy('name')->orderBy('id')
            ->get(['id', 'original_id', 'name']);
        $latest = fn (array $ids): array => $assays->whereIn('original_id', Assay::query()->whereKey($ids)->pluck('original_id'))->pluck('id')->all();

        return view('worklists.form', [
            'worklist' => $worklist,
            'assays' => $assays->map(fn (Assay $assay): array => ['id' => $assay->id, 'name' => $assay->name])->values(),
            'initial' => [
                'title' => old('title', $worklist->title),
                'subtitle' => old('subtitle', $worklist->subtitle),
                'footer_text' => old('footer_text', $worklist->footer_text),
                'no_columns' => (int) old('no_columns', $worklist->no_columns),
                'orientation' => old('orientation', $worklist->orientation),
                'analyses' => old('analyses', $latest($worklist->analyses ?? [])),
                'columns' => old('columns', array_map(
                    fn (array $column): array => [...$column, 'tick_on' => $latest($column['tick_on'])],
                    $worklist->columnList(),
                )),
                'extra_rows' => old('extra_rows', implode("\n", $worklist->extra_rows ?? [])),
                'extra_pages' => old('extra_pages', $worklist->extra_pages),
            ],
        ]);
    }
}
