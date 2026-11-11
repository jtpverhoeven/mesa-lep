<?php

namespace App\Actions\SampleBuffers;

use App\Models\Client;
use App\Models\SampleBuffer;
use App\Support\Cvars;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stores reviewed CSV rows in the sample buffer exactly like the legacy commitToBuffer did (source 1).
 *
 * Rows are expected to be validated by CommitSampleBufferImportRequest. The import is atomic: either
 * every row reaches the buffer or none does.
 */
class ImportSampleBuffers
{
    public const SOURCE_CSV = 1;

    public function __construct(private Cvars $cvars) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $metaColumns  Metadata columns to keep; each row's "meta" list follows this order.
     * @return Collection<int, SampleBuffer>
     */
    public function handle(Client $client, array $rows, array $metaColumns): Collection
    {
        $samplingMethod = (int) $this->cvars->value('MESA_STD_SMPL_METHOD');

        return DB::transaction(fn (): Collection => collect($rows)->map(
            fn (array $row): SampleBuffer => $this->store($client, $row, $metaColumns, $samplingMethod),
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $metaColumns
     */
    private function store(Client $client, array $row, array $metaColumns, int $samplingMethod): SampleBuffer
    {
        $samplingDate = CarbonImmutable::createFromFormat('!Y-m-d', $row['sampling_date']);
        $isTht = filter_var($row['tht'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $buffer = $client->sampleBuffers()->make([
            'source' => self::SOURCE_CSV,
            'project' => 'PRJ-'.$client->id.'-'.$samplingDate->format('dmY'),
            'sampling_date' => $samplingDate->format('d-m-Y'),
            'sampling_method' => $samplingMethod,
            'sample_name' => $row['sample_name'] ?? '',
            'sample_details' => $row['sample_details'] ?? '',
            'tht' => $isTht,
            'tht_date' => $isTht ? $row['tht_date'] : null,
            'meta' => $this->meta($metaColumns, $row['meta'] ?? []),
            'misc_directions' => $row['misc_directions'] ?? null,
            'authorized' => false,
            'project_name' => filled($row['project_name'] ?? null) ? $row['project_name'] : null,
            'portal_product_group_id' => $row['product_group'] ?? null,
            'date_registered' => (string) now()->timestamp,
            'sample_research_type' => '0',
            'sample_properties' => [],
        ]);

        // Legacy keeps the customer's analyses text as plain text; the array cast would store it as a JSON string.
        $buffer->setRawAttributes([...$buffer->getAttributes(), 'analyses_selected' => (string) ($row['analyses_selected'] ?? '')]);
        $buffer->save();

        return $buffer;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string|null>  $values
     * @return array<string, string>
     */
    private function meta(array $columns, array $values): array
    {
        $meta = [];

        foreach ($columns as $index => $column) {
            $value = (string) ($values[$index] ?? '');

            if ($value !== '') {
                $meta[$column] = $value;
            }
        }

        return $meta;
    }
}
