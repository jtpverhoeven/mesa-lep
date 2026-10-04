<?php

namespace App\Actions\Samples;

use App\Models\Sample;
use Illuminate\Database\Eloquent\Collection;

class CreateRodacSamples
{
    public function __construct(
        private CreateBulkSamples $create,
        private BulkSampleRegistrationOptions $options,
        private RegisterSampleInoculation $inoculate,
    ) {}

    public function handle(array $data): Collection
    {
        $data['special_type'] = 2;
        $data['sample_note'] = $data['sample_note'] ?? '';
        $data['project_extra'] = ['sample_method' => (string) $data['sampling_method'], 'number_of_blanks' => '0'];
        $data['samples'] = array_map(fn (array $row) => [
            ...$row,
            'sample_type' => 'R',
            'leg_type' => '-',
            'description' => $this->options->value('RODAC_STD_DESCRIPTION', 'Rodac afdrukje'),
            'sampling_method' => $data['sampling_method'],
            'custom_fields' => ['details' => $row['description'] ?? ''],
            'sample_extra' => ['follow' => (string) ($row['follow'] ?? ''), 'location' => $row['location'] ?? ''],
        ], $data['samples']);

        return $this->create->handle($data, function (Sample $sample): void {
            if ($this->options->value('RODAC_AUTO_REGISTER_INNOCULATION') === '1' && empty($sample->sample_innoculated)) {
                $this->inoculate->handle($sample, storedIn: '', dilutedAt: '');
            }
        });
    }
}
