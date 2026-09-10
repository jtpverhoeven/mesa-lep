<?php

namespace App\Actions\ReferenceSources;

use App\Models\ReferenceSource;
use Illuminate\Support\Facades\DB;

class UpdateReferenceSource
{
    public function handle(ReferenceSource $referenceSource, array $data): ReferenceSource
    {
        return DB::transaction(function () use ($referenceSource, $data): ReferenceSource {
            $referenceSource->update([
                'name' => [
                    'nl' => $data['name_nl'],
                    'en' => $data['name_en'] ?? '',
                ],
                'client' => ! empty($data['client']) ? (int) $data['client'] : null,
            ]);

            return $referenceSource->fresh();
        });
    }
}