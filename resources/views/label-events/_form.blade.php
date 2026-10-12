<label-event-form v-bind="{{ Illuminate\Support\Js::from([
    'initial' => [
        'event' => old('event', $labelEvent->event),
        'event_group' => old('event_group', $labelEvent->event_group),
        'event_data' => old('event_data', $labelEvent->event_data),
        'print_label' => old('print_label', $labelEvent->print_label),
        'print_to' => old('print_to', $labelEvent->print_to),
        'copies' => old('copies', $labelEvent->copies),
    ],
    'options' => $options,
    'errors' => (object) $errors->getBag('default')->messages(),
    'actionUrl' => $labelEvent->exists ? route('label-events.update', $labelEvent) : route('label-events.store'),
    'cancelUrl' => route('label-events.index', ['group' => $labelEvent->event_group]),
    'csrf' => csrf_token(),
    'updating' => $labelEvent->exists,
]) }}"></label-event-form>