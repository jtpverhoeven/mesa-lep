<?php

namespace App\Actions\ReferenceSources;

use App\Models\ReferenceSource;
use Illuminate\Support\Facades\DB;

class CreateReferenceSource
{
    public function handle(array $data): ReferenceSource
    {
        return DB::transaction(fn (): ReferenceSource => ReferenceSource::create([
            'name' => [
                'nl' => $data['name_nl'],
                'en' => $data['name_en'] ?? '',
            ],
            'client' => ! empty($data['client']) ? (int) $data['client'] : null,
        ]));
    }
}