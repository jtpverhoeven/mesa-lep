@extends('layouts.app')
@section('title', 'MESA | Werklijsten')
@section('content')
    <worklist-directory v-bind="{{ Illuminate\Support\Js::from([
        'worklists' => $worklists->map(fn ($worklist): array => ['id' => $worklist->id, 'title' => $worklist->title, 'subtitle' => $worklist->subtitle])->values(),
        'createUrl' => route('worklists.create'),
        'editUrl' => route('worklists.edit', '__WORKLIST__'),
        'destroyUrl' => route('worklists.destroy', '__WORKLIST__'),
        'csrf' => csrf_token(),
    ]) }}"></worklist-directory>
@endsection
