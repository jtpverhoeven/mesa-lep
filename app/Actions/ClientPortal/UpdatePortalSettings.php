<?php

namespace App\Actions\ClientPortal;

use App\ClientPortal\PortalSettings;
use Illuminate\Support\Facades\DB;

class UpdatePortalSettings
{
    public function __construct(private readonly PortalSettings $settings) {}

    /**
     * @param  array{acceptType: string, bearer: string}  $values
     */
    public function handle(array $values): void
    {
        DB::transaction(function () use ($values): void {
            $this->settings->set('acceptType', $values['acceptType']);
            $this->settings->set('bearer', $values['bearer']);
        });
    }
}
