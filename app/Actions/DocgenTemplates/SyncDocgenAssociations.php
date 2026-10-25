<?php

namespace App\Actions\DocgenTemplates;

use App\Models\Assay;
use App\Models\DocgenTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncDocgenAssociations
{
    /** @param list<int|string> $assayIds */
    public function handle(DocgenTemplate $template, array $assayIds, bool $includeInactive = true): void
    {
        DB::transaction(function () use ($template, $assayIds, $includeInactive): void {
            $template = DocgenTemplate::query()->lockForUpdate()->findOrFail($template->id);
            $assays = Assay::query()->whereKey($assayIds)->get(['id', 'original_id']);
            $associations = $assays->mapWithKeys(fn (Assay $assay): array => [
                $assay->id => ['original_assay_id' => $assay->original_id],
            ])->all();

            if (! $includeInactive) {
                foreach ($template->assays()->where('assays.active', 0)->get() as $assay) {
                    $associations[$assay->id] = ['original_assay_id' => $assay->pivot->original_assay_id];
                }
            }

            $template->assays()->sync($associations);
        });
    }

    public function copy(DocgenTemplate $target, DocgenTemplate $source): void
    {
        if ($target->is($source)) {
            throw ValidationException::withMessages(['source_template' => 'Selecteer een ander rapportsjabloon.']);
        }

        DB::transaction(function () use ($target, $source): void {
            DocgenTemplate::query()->whereKey([$target->id, $source->id])->orderBy('id')->lockForUpdate()->get();
            $associations = $source->associations()->get(['assay_id', 'original_assay_id'])->toArray();
            $target->associations()->delete();
            $target->associations()->createMany($associations);
        });
    }
}
