<?php

namespace App\Actions\LabelEvents;

use App\Models\LabelEvent;

class DeleteLabelEvent
{
    public function handle(LabelEvent $labelEvent): void
    {
        $labelEvent->delete();
    }
}
