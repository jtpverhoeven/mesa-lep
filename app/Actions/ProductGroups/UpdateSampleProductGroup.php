<?php

namespace App\Actions\ProductGroups;

use App\ClientPortal\ClientPortalService;
use App\Models\ProductGroup;
use App\Models\Sample;
use Illuminate\Validation\ValidationException;

class UpdateSampleProductGroup
{
    public function __construct(private ClientPortalService $clientPortal) {}

    public function handle(Sample $sample, ProductGroup $productGroup): Sample
    {
        if ($sample->portal_sample_id) {
            $response = $this->clientPortal->updateProductGroup($sample->portal_sample_id, $productGroup->portal_id);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'product_group_id' => [$response->json('message') ?? 'De productgroep kon niet in het klantportaal worden bijgewerkt.'],
                ]);
            }
        }

        $sample->update(['portal_product_group_id' => $productGroup->portal_id]);

        return $sample->fresh('productGroup');
    }
}
