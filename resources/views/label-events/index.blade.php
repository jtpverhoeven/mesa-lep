@extends('layouts.app')
@section('title', 'MESA | Labelregels')
@section('content')
    <label-event-directory v-bind="{{ Illuminate\Support\Js::from([
        'labelEvents' => $labelEvents,
        'options' => $options,
        'initialGroup' => (string) request('group', '1'),
        'createUrl' => route('label-events.create'),
        'editUrl' => route('label-events.edit', '__LABEL_EVENT__'),
        'destroyUrl' => route('label-events.destroy', '__LABEL_EVENT__'),
        'csrf' => csrf_token(),
    ]) }}"></label-event-directory>
@endsection