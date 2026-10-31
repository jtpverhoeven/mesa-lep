<?php

namespace App\Actions\ProductGroups;

use App\Actions\Projects\EnsureEditable;
use App\ClientPortal\ClientPortalService;
use App\Models\ProductGroup;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSampleProductGroup
{
    public function __construct(private ClientPortalService $clientPortal, private EnsureEditable $ensureEditable) {}

    public function handle(Sample $sample, ProductGroup $productGroup): Sample
    {
        DB::transaction(fn (): Sample => $this->ensureEditable->forSample($sample->id));

        if ($sample->portal_sample_id) {
            $response = $this->clientPortal->updateProductGroup($sample->portal_sample_id, $productGroup->portal_id);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'product_group_id' => [$response->json('message') ?? 'De productgroep kon niet in het klantportaal worden bijgewerkt.'],
                ]);
            }
        }

        DB::transaction(function () use ($sample, $productGroup): void {
            $this->ensureEditable->forSample($sample->id)->update(['portal_product_group_id' => $productGroup->portal_id]);
        });

        return $sample->fresh('productGroup');
    }
}
