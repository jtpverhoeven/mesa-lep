<?php

namespace App\Actions\Assays;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\Assay;
use App\Models\AssayField;
use Illuminate\Support\Facades\DB;

class CreateAssay
{
    public function handle(array $data): Assay
    {
        return DB::transaction(function () use ($data) {
            $customFields = AssayField::where('active', 1)->orderBy('position')->get()->mapWithKeys(
                fn (AssayField $field) => [$field->name => $data['custom_fields'][$field->id] ?? $field->standard_value]
            );

            $assay = Assay::create([
                'original_id' => 0,
                'name' => $data['name'],
                'type_base' => $data['type_base'],
                'type' => $data['type'],
                'media_id' => json_encode(array_values($data['media'] ?? []), JSON_THROW_ON_ERROR),
                'meta_assays' => (int) $data['type'] === 4 ? implode(',', $data['meta_assays'] ?? []) : '',
                'dillution' => $data['dillution'],
                'replicates' => $data['replicates'],
                'min_count' => $data['min_count'],
                'max_count' => $data['max_count'],
                'duration' => $data['duration'] ?? '',
                'start_from' => $data['start_from'],
                'confirmation' => $data['confirmation'] ?? 0,
                'confirmation_type' => $data['confirmation_type'] ?? 1,
                'confirmation_init' => $data['confirmation_init'] ?? 0,
                'confirmation_depth' => array_key_exists('confirmation_depth', $data)
                    ? $data['confirmation_depth']
                    : 5,
                'confirmation_script' => array_key_exists('confirmation_script', $data)
                    ? AssayConfirmationConfiguration::serializeScript($data['confirmation_script'])
                    : '[]',
                'confirmation_support' => array_key_exists('confirmation_support', $data)
                    ? AssayConfirmationConfiguration::serializeSupport($data['confirmation_support'])
                    : null,
                'show_conf_table' => array_key_exists('show_conf_table', $data)
                    ? $data['show_conf_table']
                    : null,
                'custom_fields' => json_encode((object) $customFields->all(), JSON_THROW_ON_ERROR),
                'script' => $data['script'] ?? '',
                'hide_report' => $data['hide_report'],
                'uses_indicator' => $data['uses_indicator'],
                'uses_trip_indicator' => $data['uses_trip_indicator'],
                'article_code' => $data['article_code'] ?? '',
                'billable' => $data['billable'],
            ]);

            $assay->update(['original_id' => $assay->id]);
            $assay->matrices()->sync($data['matrices'] ?? []);

            return $assay;
        });
    }
}
