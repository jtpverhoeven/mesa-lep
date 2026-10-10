<?php

namespace App\Actions\ChangeTracking;

use App\ChangeTracking\ChangeType;
use App\Models\ChangeRevision;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;

class GetChangeRevisions
{
    /** @param list<string> $types
     * @return array{data: array, meta: array, types: array}
     */
    public function handle(string $scope, int $id, array $types = [], int $page = 1, ?int $sampleId = null): array
    {
        $query = ChangeRevision::query();
        if ($scope === 'project') {
            $query->where(function (Builder $query) use ($id): void {
                $query->where('project', $id)->orWhere(function (Builder $query) use ($id): void {
                    $query->where(function (Builder $query): void {
                        $query->whereNull('project')->orWhere('project', 0);
                    })->whereIn('sample', Sample::query()->where('project', $id)->select('id'));
                });
            });
        } else {
            $column = match ($scope) {
                'sample' => 'sample', 'analysis' => 'said', 'assurance-form' => 'assurance_form',
            };
            $query->where($column, $id);
            if ($scope === 'analysis') {
                $query->where('sample', $sampleId);
            }
        }

        $availableTypes = (clone $query)->distinct()->pluck('type')->map(fn (string $type): array => [
            'value' => $type, 'label' => ChangeType::label($type),
        ])->sortBy('label')->values()->all();
        if ($types !== []) {
            $query->whereIn('type', $types);
        }
        $revisions = $query->with('user:id,name')->orderByDesc('id')->paginate(25, ['*'], 'page', $page);

        return [
            'data' => $revisions->getCollection()->map(fn (ChangeRevision $revision): array => [
                ...$revision->only(['id', 'user_id', 'timestamp', 'type', 'project', 'sample', 'said', 'assurance_form', 'event', 'from', 'to']),
                'user_name' => $revision->user?->name ?? 'Gebruiker #'.$revision->user_id,
                'type_label' => ChangeType::label($revision->type),
            ])->all(),
            'meta' => ['current_page' => $revisions->currentPage(), 'last_page' => $revisions->lastPage(), 'total' => $revisions->total()],
            'types' => $availableTypes,
        ];
    }
}
