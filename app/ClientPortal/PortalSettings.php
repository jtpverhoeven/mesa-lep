<?php

namespace App\ClientPortal;

use App\Models\PortalSetting;

class PortalSettings
{
    public function acceptType(): string
    {
        return $this->get('acceptType', 'application/json');
    }

    public function bearer(): string
    {
        return $this->get('bearer');
    }

    public function lastSync(): ?string
    {
        return $this->get('lastSync') ?: null;
    }

    public function set(string $key, string $value): void
    {
        PortalSetting::query()->updateOrCreate(
            ['pk' => $key],
            ['pv' => $value],
        );
    }

    /**
     * @return array{acceptType: string, bearer: string, lastSync: ?string}
     */
    public function connection(): array
    {
        $settings = PortalSetting::query()
            ->whereIn('pk', ['acceptType', 'bearer', 'lastSync'])
            ->pluck('pv', 'pk');

        return [
            'acceptType' => (string) $settings->get('acceptType', 'application/json'),
            'bearer' => (string) $settings->get('bearer', ''),
            'lastSync' => $settings->get('lastSync') ?: null,
        ];
    }

    private function get(string $key, string $default = ''): string
    {
        return (string) (PortalSetting::query()->where('pk', $key)->value('pv') ?? $default);
    }
}
