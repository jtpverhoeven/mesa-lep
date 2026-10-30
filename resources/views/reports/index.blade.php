@extends('layouts.app')
@section('title', $temporary ? 'MESA | Voorlopig rapport genereren' : 'MESA | Rapport genereren')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>{{ $temporary ? 'Voorlopig rapport genereren' : 'Rapport genereren' }}: {{ $project->reference ?: $project->id }}</h1></div>
        <div class="flex flex-wrap gap-2">
            <a class="button" href="{{ route($temporary ? 'projects.reports.index' : 'projects.reports.temporary.index', $project) }}">{{ $temporary ? 'Volledig rapport' : 'Voorlopig rapport' }}</a>
            <a class="button" href="{{ route('projects.search', ['project' => $project->id]) }}">Terug naar project</a>
        </div>
    </div>
    <project-report-form :config="{{ Illuminate\Support\Js::from($form) }}"></project-report-form>
@endsection
