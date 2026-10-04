<?php

namespace App\Actions\Samples;

use App\Models\Cvar;
use App\Models\ProjectField;
use App\Models\ResearchProfile;
use App\Models\SampleProcedure;

class BulkSampleRegistrationOptions
{
    private ?array $settings = null;

    public function value(string $name, string $default = ''): string
    {
        $this->settings ??= Cvar::query()->pluck('value', 'cvar')->all();

        return (string) ($this->settings[$name] ?? $default);
    }

    public function ids(string $name): array
    {
        return array_map('intval', json_decode($this->value($name, '[]'), true) ?: []);
    }

    public function profiles(): array
    {
        $matrices = [];
        foreach (['A', 'B', 'C'] as $matrix) {
            foreach ($this->ids('LEGIONELLA_PROFILES_IN_MATRIX_'.$matrix) as $id) {
                $matrices[$id] ??= $matrix;
            }
        }

        return ResearchProfile::query()
            ->where('global', 1)
            ->whereIn('id', $this->ids('LEGIONELLA_ALLOWED_PROFILES'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (ResearchProfile $profile) => isset($matrices[$profile->id]))
            ->map(fn (ResearchProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'matrix' => $matrices[$profile->id],
            ])
            ->values()->all();
    }

    public function samplingMethods(string $type): array
    {
        $methods = SampleProcedure::query()->where('active', 1)
            ->whereIn('id', $this->ids('SMPL_METHOD_AVAIL_'.strtoupper($type)))
            ->orderBy('id')->get(['id', 'name'])->toArray();

        return $type === 'rodac' ? [...$methods, ['id' => 0, 'name' => 'Onbekend']] : $methods;
    }

    public function handle(string $type, BarcodeGenerator $barcodes): array
    {
        $profiles = $type === 'legionella' ? $this->profiles() : [];

        return [
            'project_fields' => ProjectField::query()->orderBy('position')->get(['name', 'alias', 'type', 'std_value', 'keep_current']),
            'sampling_methods' => $this->samplingMethods($type),
            'default_sampling_method' => (int) $this->value($type === 'legionella' ? 'MESA_STD_LEGSMPL_METHOD' : 'MESA_STD_RODACSMPL_METHOD'),
            'profiles' => $profiles,
            'default_profile' => (int) $this->value('LEGIONELLA_STD_MATRIX'),
            'barcodes' => [
                'normal' => $barcodes->predict(),
                'A' => $barcodes->predict(matrix: 'A'),
                'B' => $barcodes->predict(matrix: 'B'),
                'C' => $barcodes->predict(matrix: 'C'),
            ],
        ];
    }
}
