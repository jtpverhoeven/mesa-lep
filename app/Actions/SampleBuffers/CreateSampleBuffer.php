<?php

namespace App\Actions\SampleBuffers;

use App\Models\Project;
use App\Models\SampleBuffer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateSampleBuffer
{
    public function handle(array $data): SampleBuffer
    {
        return DB::transaction(function () use ($data): SampleBuffer {
            $samplingDate = isset($data['sampling_date']) ? CarbonImmutable::parse($data['sampling_date']) : null;
            $receiveDate = isset($data['receive_date']) ? CarbonImmutable::parse($data['receive_date']) : null;
            $isTht = $data['register_as'] === 'tht';
            $projectName = ($data['project_name'] ?? null) ?: Project::query()->whereKey($data['project'] ?? null)->value('project_name');

            if ($isTht) {
                DB::statement("select pg_advisory_xact_lock(hashtext('mesa-lims-tht-code'))");
            }

            return SampleBuffer::create([
                'client' => $data['client'],
                'source' => 2,
                'project' => 'PRJ-'.$data['client'].'-'.$samplingDate?->format('dmY'),
                'sampling_date' => $samplingDate?->format('d-m-Y') ?? '',
                'sampling_method' => $data['sampling_method'] ?? 0,
                'sample_name' => $data['description'] ?? '',
                'sample_details' => $data['custom_fields']['details'] ?? '',
                'tht' => $isTht,
                'tht_date' => $isTht ? ($data['tht_date'] ?? null) : null,
                'meta' => $data['custom_fields'] ?? [],
                'analyses_selected' => $data['analyses'] ?? [],
                'misc_directions' => $isTht && isset($data['tht_storage']) ? 'Opslag: '.$this->storageLabel($data['tht_storage']) : null,
                'authorized' => $isTht,
                'project_name' => $projectName,
                'portal_order_info' => [
                    'project_id' => $data['project'] ?? null,
                    'project_custom_fields' => $data['project_custom_fields'] ?? [],
                    'sample_note' => $data['sample_note'] ?? null,
                ],
                'receive_time' => $data['receive_time'] ?? null,
                'receive_date' => $receiveDate?->format('d-m-Y'),
                'tht_code' => $isTht ? app(ThtCodeGenerator::class)->next() : null,
                'date_registered' => (string) now()->timestamp,
                'sample_research_type' => '0',
                'sample_properties' => [],
            ]);
        });
    }

    private function storageLabel(string $storage): string
    {
        return match ($storage) {
            '4' => '+3°C',
            '0' => '+4°C',
            '1' => '+7°C',
            '2' => '-18°C',
            '3' => 'Kamer temperatuur',
        };
    }
}
