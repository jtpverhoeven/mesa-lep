@extends('layouts.app')
@section('title', 'MESA | Project niet afgerond')
@section('content')
    {{-- <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>Rapport genereren: {{ $project->reference ?: $project->id }}</h1></div>
    </div> --}}
    <section class="sample-panel" aria-labelledby="report-not-ready">
        <h2 id="report-not-ready">Project {{ $project->reference ?: $project->id }} niet afgerond</h2>
        <div class="sample-panel-body grid gap-4">
            <p>Het project was niet afgerond op het moment van autoriseren. Weet u zeker dat u geen voorlopig rapport wilt aanmaken?</p>
            <p class="text-muted">U kunt toch doorgaan met het genereren van een rapport. De bestaande watermerken blijven van toepassing.</p>
            <div class="flex flex-wrap gap-2">
                <a class="button primary" href="{{ route('projects.reports.temporary.index', $project) }}">Voorlopig rapport genereren</a>
                <a class="button" href="{{ route('projects.reports.index', ['project' => $project, 'continue' => 1]) }}">Toch doorgaan</a>
                <a class="button" href="{{ route('projects.search', ['project' => $project->id]) }}">Open project</a>
                <a class="button" href="{{ route('projects.overview.completed') }}">Overzicht afgeronde projecten</a>
                <a class="button" href="{{ route('projects.overview.authorized') }}">Overzicht geautoriseerde projecten</a>
            </div>
        </div>
    </section>
@endsection
