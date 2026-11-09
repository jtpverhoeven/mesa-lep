@extends('layouts.app')
@section('title', 'MESA | Rapport verzenden')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>Rapport verzenden: {{ $project['reference'] ?: $project['id'] }}</h1></div>
        <div class="flex flex-wrap gap-2">
            <a class="button" href="{{ route('projects.search', ['project' => $project['id']]) }}">Terug naar project</a>
        </div>
    </div>
    <report-sender :config="{{ Illuminate\Support\Js::from($config) }}"></report-sender>
@endsection
