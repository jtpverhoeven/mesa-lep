@extends('layouts.app')
@section('title', 'MESA | Rapport genereren')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>Rapport genereren: {{ $project->reference ?: $project->id }}</h1></div>
        <a class="button" href="{{ route('projects.search', ['project' => $project->id]) }}">Terug naar project</a>
    </div>
    <project-report-form :config="{{ Illuminate\Support\Js::from($form) }}"></project-report-form>
@endsection
