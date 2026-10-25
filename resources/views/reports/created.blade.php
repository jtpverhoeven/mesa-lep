@extends('layouts.app')
@section('title', 'MESA | Aangemaakte rapporten')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>Rapporten aangemaakt: {{ $project->reference }}</h1></div>
        <a class="button" href="{{ route('projects.reports.index', $project) }}">Terug naar rapportinstellingen</a>
    </div>
    <div class="grid gap-4 lg:grid-cols-[minmax(15rem,1fr)_3fr]">
        <section>
            <h2>Individuele rapporten</h2>
            <ul class="grid gap-3">
                @foreach($exports as $export)
                    <li><a class="text-link" href="{{ route('projects.reports.show', [$project, $export]) }}" target="report-pdf">{{ $export->filename() }}</a></li>
                @endforeach
            </ul>
        </section>
        <iframe class="min-h-[75vh] w-full rounded-control border border-line bg-surface" name="report-pdf" title="Aangemaakt PDF-rapport" src="{{ route('projects.reports.show', [$project, $exports->first()]) }}"></iframe>
    </div>
@endsection
