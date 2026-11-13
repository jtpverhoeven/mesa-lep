@extends('layouts.app')
@section('title', 'MESA | Werklijsten printen')
@section('content')
    <worklist-print v-bind="{{ Illuminate\Support\Js::from([
        'worklists' => $worklists->map(fn ($worklist): array => ['id' => $worklist->id, 'title' => $worklist->title])->values(),
        'printUrl' => route('worklists.pdf'),
        'today' => now(App\AssuranceForms\AssuranceValueAssessment::TIMEZONE)->format('Y-m-d'),
        'errors' => (object) $errors->getBag('default')->messages(),
    ]) }}"></worklist-print>
@endsection
