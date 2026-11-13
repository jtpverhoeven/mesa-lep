@extends('layouts.app')
@section('title', 'MESA | Monsterfoto\'s')
@section('content')
    <sample-photos v-bind="{{ Illuminate\Support\Js::from([
        'lookupUrl' => route('samples.photos.data'),
        'uploadUrl' => route('samples.files.store', ['sample' => '__SAMPLE__']),
        'maxEdge' => $maxEdge,
        'canViewProject' => auth()->user()->can('projects.view'),
    ]) }}"></sample-photos>
@endsection
