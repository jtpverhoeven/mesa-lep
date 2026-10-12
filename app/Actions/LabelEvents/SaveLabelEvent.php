<?php

namespace App\Actions\LabelEvents;

use App\Models\LabelEvent;

class SaveLabelEvent
{
    /**
     * @param  array{event: string, event_group: int|string, print_label: int|string, print_to: int|string, copies: int|string, event_data?: array{type: string, inspect?: string, inspect_value: string|int|null, negative: string|int}}  $data
     */
    public function handle(LabelEvent $labelEvent, array $data): LabelEvent
    {
        $eventData = '';

        if ($data['event'] === 'L') {
            $input = $data['event_data'];
            $condition = ['type' => $input['type']];

            if ($input['type'] === 'sField') {
                $condition['inspect'] = $input['inspect'];
            }

            $condition['inspect_value'] = (string) $input['inspect_value'];
            $condition['negative'] = (string) $input['negative'];
            $eventData = json_encode($condition, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
        }

        $labelEvent->fill([
            'event' => $data['event'],
            'event_group' => $data['event_group'],
            'event_data' => $eventData,
            'print_label' => $data['print_label'],
            'print_to' => $data['print_to'],
            'copies' => $data['copies'],
        ])->save();

        return $labelEvent;
    }
}
