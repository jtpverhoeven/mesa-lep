<?php

namespace App\Actions\Assays;

use App\Models\Assay;
use App\Models\AssayField;
use Illuminate\Support\Facades\DB;

class UpdateAssay
{
    public function handle(Assay $assay, array $data): Assay
    {
        return DB::transaction(function () use ($assay, $data) {
            $attributes = $this->attributes($assay, $data);
            $forceChanges = filter_var($data['force_changes'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($forceChanges) {
                $assay->fill($attributes);
                $assay->save();
                $updatedAssay = $assay;
            } else {
                $updatedAssay = $assay->replicate();
                $updatedAssay->fill($attributes);
                $updatedAssay->save();
                $assay->update(['active' => 0]);
            }

            $updatedAssay->matrices()->sync($data['matrices'] ?? []);

            return $updatedAssay->fresh();
        });
    }

    private function attributes(Assay $assay, array $data): array
    {
        $type = (int) ($data['type'] ?? $assay->type);

        return [
            'name' => $data['name'],
            'type_base' => $data['type_base'],
            'type' => $type,
            'media_id' => json_encode(array_values($data['media'] ?? []), JSON_THROW_ON_ERROR),
            'meta_assays' => $type === 4
                ? implode(',', $data['meta_assays'] ?? [])
                : '',
            'dillution' => $data['dillution'] ?? $assay->dillution,
            'replicates' => $data['replicates'] ?? $assay->replicates,
            'min_count' => $data['min_count'] ?? $assay->min_count,
            'max_count' => $data['max_count'] ?? $assay->max_count,
            'duration' => $data['duration'] ?? $assay->duration,
            'start_from' => $this->startFrom($assay, $data),
            'confirmation' => $data['confirmation'] ?? $assay->confirmation,
            'confirmation_type' => $data['confirmation_type'] ?? $assay->confirmation_type,
            'confirmation_init' => $data['confirmation_init'] ?? $assay->confirmation_init,
            'confirmation_depth' => array_key_exists('confirmation_depth', $data)
                ? $data['confirmation_depth']
                : $assay->confirmation_depth,
            'confirmation_script' => $data['confirmation_script'] ?? $assay->confirmation_script,
            'confirmation_support' => $data['confirmation_support'] ?? $assay->confirmation_support,
            'show_conf_table' => $data['show_conf_table'] ?? $assay->show_conf_table,
            'hide_report' => $data['hide_report'] ?? $assay->hide_report,
            'uses_indicator' => $data['uses_indicator'] ?? $assay->uses_indicator,
            'uses_trip_indicator' => $data['uses_trip_indicator'] ?? $assay->uses_trip_indicator,
            'article_code' => $data['article_code'] ?? $assay->article_code,
            'billable' => array_key_exists('billable', $data) ? $data['billable'] : $assay->billable,
            'script' => $data['script'] ?? $assay->script,
            'custom_fields' => $this->customFields($assay, $data),
        ];
    }

    private function customFields(Assay $assay, array $data): string
    {
        $existing = json_decode((string) $assay->custom_fields, true);
        $existing = is_array($existing) ? $existing : [];
        $submitted = $data['custom_fields'] ?? null;

        $customFields = AssayField::query()
            ->where('active', 1)
            ->orderBy('position')
            ->get()
            ->mapWithKeys(function (AssayField $field) use ($existing, $submitted) {
                if (is_array($submitted)) {
                    $value = $submitted[$field->id] ?? $field->standard_value;
                } else {
                    $value = $existing[$field->name] ?? $field->standard_value;
                }

                return [$field->name => $value];
            });

        return json_encode((object) $customFields->all(), JSON_THROW_ON_ERROR);
    }

    private function startFrom(Assay $assay, array $data): string
    {
        if (! array_key_exists('start_anchor', $data)) {
            return (string) $assay->start_from;
        }

        if (in_array($data['start_anchor'], ['r', 'i'], true)) {
            return $data['start_anchor'];
        }

        return $data['start_anchor'].':'.($data['start_field_name'] ?? '');
    }
}