<?php

namespace App\Http\Controllers;

use App\Actions\Assays\CreateAssay;
use App\Actions\Assays\UpdateAssay;
use App\Http\Requests\StoreAssayRequest;
use App\Http\Requests\UpdateAssayRequest;
use App\Models\Assay;
use App\Models\AssayField;
use App\Models\AssayType;
use App\Models\Matrix;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssayController extends Controller
{
    public function index(): View
    {
        return view('assays.index', [
            'assays' => Assay::with('assayType')->where('active', 1)->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('assays.create', [
            'types' => AssayType::where('active', 1)->orderBy('name')->get(),
            'media' => Media::where('active', 1)->orderBy('name')->get(),
            'matrices' => Matrix::where('active', 1)->orderBy('name')->get(),
            'fields' => AssayField::where('active', 1)->orderBy('position')->get(),
            'assays' => Assay::where('active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreAssayRequest $request, CreateAssay $createAssay): RedirectResponse
    {
        $assay = $createAssay->handle($request->validated());

        return to_route('assays.index')->with('success', 'Analyse "'.$assay->name.'" is aangemaakt.');
    }

    public function edit(Assay $assay): View
    {
        $revisions = $assay->revisions()->reorder('id', 'desc')->get();
        $startAnchor = (string) $assay->start_from;
        $startFieldName = '';

        if (str_contains($startAnchor, ':')) {
            [$startAnchor, $startFieldName] = array_pad(explode(':', $startAnchor, 2), 2, '');
        }

        if (! in_array($startAnchor, ['r', 'i', 'p', 's'], true)) {
            $startAnchor = 'r';
        }

        $mediaIds = json_decode((string) $assay->media_id, true);
        $mediaIds = is_array($mediaIds) ? $mediaIds : array_filter(explode(',', (string) $assay->media_id));
        $mediaIds = collect($mediaIds)
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $metaAssays = array_filter(explode(',', (string) $assay->meta_assays), static fn (string $id): bool => $id !== '');
        $customValues = json_decode((string) $assay->custom_fields, true);

        return view('assays.edit', [
            'assay' => $assay,
            'revisions' => $revisions,
            'isTip' => $revisions->first()?->id === $assay->id,
            'types' => AssayType::where('active', 1)->orderBy('name')->get(),
            'media' => Media::query()
                ->where('active', 1)
                ->when($mediaIds, fn ($query) => $query->orWhereIn('id', $mediaIds))
                ->orderBy('name')
                ->get(),
            'matrices' => Matrix::where('active', 1)->orderBy('name')->get(),
            'selectedMedia' => $mediaIds,
            'selectedMatrices' => $assay->matrices->modelKeys(),
            'selectedMetaAssays' => $metaAssays,
            'assays' => Assay::where('active', 1)->orderBy('name')->get(['id', 'name']),
            'fields' => AssayField::where('active', 1)->orderBy('position')->get(),
            'customValues' => is_array($customValues) ? $customValues : [],
            'startAnchor' => $startAnchor,
            'startFieldName' => $startFieldName,
        ]);
    }

    public function update(UpdateAssayRequest $request, Assay $assay, UpdateAssay $updateAssay): RedirectResponse
    {
        $updatedAssay = $updateAssay->handle($assay, $request->validated());

        return to_route('assays.edit', $updatedAssay)->with('success', 'Analyse "'.$updatedAssay->name.'" is bijgewerkt.');
    }
}
