<?php

namespace App\Http\Controllers;

use App\Actions\Assays\CreateAssay;
use App\Actions\Assays\UpdateAssay;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Http\Requests\StoreAssayRequest;
use App\Http\Requests\UpdateAssayRequest;
use App\Models\Assay;
use App\Models\AssayField;
use App\Models\AssayType;
use App\Models\ConfirmationTable;
use App\Models\Matrix;
use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AssayController extends Controller
{
    public function index(): View
    {
        $typeLabels = [1 => 'Telling', 2 => 'Detectie', 3 => 'Overig', 4 => 'Meta'];
        $assays = Assay::query()
            ->with('assayType:id,name')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'type_base', 'type', 'dillution', 'replicates', 'article_code'])
            ->map(fn (Assay $assay): array => [
                'id' => $assay->id,
                'name' => $assay->name,
                'assayType' => $assay->assayType?->name ?? '-',
                'typeLabel' => $typeLabels[$assay->type] ?? $assay->type,
                'dilution' => $assay->dillution ? 'Ja' : 'Nee',
                'replicates' => $assay->replicates ? 'Ja' : 'Nee',
                'articleCode' => $assay->article_code ?: '-',
            ])
            ->values();

        return view('assays.index', [
            'assays' => $assays,
        ]);
    }

    public function create(): View
    {
        $confirmationScript = AssayConfirmationConfiguration::decode(old('confirmation_script', '[]'));
        $confirmationSupport = AssayConfirmationConfiguration::decode(old('confirmation_support'));
        $confirmationTables = ConfirmationTable::query()->orderBy('name')->get(['id', 'name']);

        return view('assays.create', [
            'types' => AssayType::where('active', 1)->orderBy('name')->get(),
            'media' => Media::where('active', 1)->orderBy('name')->get(),
            'matrices' => Matrix::where('active', 1)->orderBy('name')->get(),
            'fields' => AssayField::where('active', 1)->orderBy('position')->get(),
            'assays' => Assay::where('active', 1)->orderBy('name')->get(['id', 'name']),
            'confirmationMedia' => $this->confirmationMedia($this->mediaIds($confirmationScript)),
            'confirmationSupportMedia' => $this->confirmationSupportMedia($this->mediaIds($confirmationSupport)),
            'confirmationTables' => $this->confirmationTables($confirmationTables),
            'confirmationConfig' => $this->confirmationConfig(
                $confirmationScript,
                $confirmationSupport,
                old('confirmation'),
                old('confirmation_type', 1),
                old('confirmation_init', 0),
                old('confirmation_depth', 5),
                old('show_conf_table'),
            ),
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
        $selectedMetaAssayReferences = old('meta_assays', $metaAssays);
        $metaAssayIds = collect($metaAssays)
            ->merge((array) $selectedMetaAssayReferences)
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $metaAssaySelection = $this->metaAssaySelection($metaAssayIds, (array) $selectedMetaAssayReferences);
        $customValues = json_decode((string) $assay->custom_fields, true);
        $confirmationScript = AssayConfirmationConfiguration::decode(
            old('confirmation_script', $assay->confirmation_script),
        );
        $confirmationSupport = AssayConfirmationConfiguration::decode(
            old('confirmation_support', $assay->confirmation_support),
        );
        $confirmationTables = ConfirmationTable::query()->orderBy('name')->get(['id', 'name']);
        $confirmationMediaIds = $this->mediaIds($confirmationScript);
        $confirmationSupportMediaIds = $this->mediaIds($confirmationSupport);

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
            'selectedMetaAssays' => $metaAssaySelection['selected'],
            'assays' => $metaAssaySelection['options'],
            'fields' => AssayField::where('active', 1)->orderBy('position')->get(),
            'customValues' => is_array($customValues) ? $customValues : [],
            'startAnchor' => $startAnchor,
            'startFieldName' => $startFieldName,
            'confirmationMedia' => $this->confirmationMedia($confirmationMediaIds),
            'confirmationSupportMedia' => $this->confirmationSupportMedia($confirmationSupportMediaIds),
            'confirmationTables' => $this->confirmationTables($confirmationTables),
            'confirmationConfig' => $this->confirmationConfig(
                $confirmationScript,
                $confirmationSupport,
                old('confirmation', $assay->confirmation),
                old('confirmation_type', $assay->confirmation_type),
                old('confirmation_init', $assay->confirmation_init),
                old('confirmation_depth', $assay->confirmation_depth),
                old('show_conf_table', $assay->show_conf_table),
            ),
        ]);
    }

    /**
     * @return array{options: Collection, selected: list<string>}
     */
    private function metaAssaySelection(array $references, ?array $selectedReferences = null): array
    {
        $referenceIds = collect($references)
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $selectedReferenceIds = collect($selectedReferences ?? $references)
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $activeAssays = Assay::query()
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'original_id']);

        if ($referenceIds->isEmpty()) {
            return ['options' => $activeAssays, 'selected' => []];
        }

        $originalIds = $this->metaAssayOriginalIds($referenceIds);
        $selectedOriginalIds = $this->metaAssayOriginalIds($selectedReferenceIds);
        $selectedActiveAssays = $activeAssays
            ->filter(fn (Assay $assay): bool => $selectedOriginalIds->contains($this->assayOriginalId($assay)))
            ->values();
        $missingOriginalIds = $originalIds
            ->diff($selectedActiveAssays->map(fn (Assay $assay): int => $this->assayOriginalId($assay)))
            ->values();
        $fallbackAssays = collect();

        if ($missingOriginalIds->isNotEmpty()) {
            $fallbackAssays = Assay::query()
                ->whereIn('original_id', $missingOriginalIds->all())
                ->orderByDesc('id')
                ->get(['id', 'name', 'original_id'])
                ->groupBy(fn (Assay $assay): int => $this->assayOriginalId($assay))
                ->map(fn (Collection $revisions): Assay => $revisions->first())
                ->values();
        }

        return [
            'options' => $activeAssays->concat($fallbackAssays)->sortBy('name')->values(),
            'selected' => $selectedActiveAssays
                ->concat($fallbackAssays->filter(
                    fn (Assay $assay): bool => $selectedOriginalIds->contains($this->assayOriginalId($assay)),
                ))
                ->map(fn (Assay $assay): string => (string) $assay->id)
                ->values()
                ->all(),
        ];
    }

    private function metaAssayOriginalIds(Collection $references): Collection
    {
        if ($references->isEmpty()) {
            return collect();
        }

        return Assay::query()
            ->where(function (Builder $query) use ($references): void {
                $query->whereIn('id', $references->all())
                    ->orWhereIn('original_id', $references->all());
            })
            ->get(['id', 'original_id'])
            ->map(fn (Assay $assay): int => $this->assayOriginalId($assay))
            ->unique()
            ->values();
    }

    private function assayOriginalId(Assay $assay): int
    {
        return (int) ($assay->original_id ?: $assay->id);
    }

    private function confirmationMedia(array $selectedIds = []): array
    {
        return Media::query()
            ->where(function ($query) use ($selectedIds) {
                $query->where('active', 1)->where('confirmation_media', 1);

                if ($selectedIds !== []) {
                    $query->orWhereIn('id', $selectedIds);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Media $medium): array => $this->mediaOption($medium))
            ->values()
            ->all();
    }

    private function confirmationSupportMedia(array $selectedIds = []): array
    {
        return Media::query()
            ->where(function ($query) use ($selectedIds) {
                $query->where('active', 1)->where(function ($query) {
                    $query->where('confirmation_media', 1)->orWhere('type', 3);
                });

                if ($selectedIds !== []) {
                    $query->orWhereIn('id', $selectedIds);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Media $medium): array => $this->mediaOption($medium))
            ->values()
            ->all();
    }

    private function confirmationTables($tables): array
    {
        return $tables->map(fn (ConfirmationTable $table): array => [
            'id' => (string) $table->id,
            'name' => (string) $table->name,
        ])->values()->all();
    }

    private function mediaOption(Media $medium): array
    {
        return [
            'id' => (string) $medium->id,
            'name' => (string) $medium->name,
            'short_name' => $medium->short_name,
            'active' => (int) $medium->active === 1,
        ];
    }

    private function mediaIds(array $rows): array
    {
        return collect($rows)
            ->pluck('mediaId')
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function confirmationConfig(
        array $script,
        array $support,
        mixed $enabled,
        mixed $type,
        mixed $initiation,
        mixed $depth,
        mixed $selectedTable,
    ): array {
        return [
            'enabled' => filter_var($enabled, FILTER_VALIDATE_BOOLEAN),
            'type' => (int) $type,
            'initiation' => (int) $initiation,
            'depth' => $depth,
            'script' => $script,
            'support' => $support,
            'selectedTable' => $selectedTable,
        ];
    }

    public function update(UpdateAssayRequest $request, Assay $assay, UpdateAssay $updateAssay): RedirectResponse
    {
        $updatedAssay = $updateAssay->handle($assay, $request->validated());

        return to_route('assays.edit', $updatedAssay)->with('success', 'Analyse "'.$updatedAssay->name.'" is bijgewerkt.');
    }
}
