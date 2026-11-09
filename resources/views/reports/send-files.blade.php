@extends('layouts.app')
@section('title', 'MESA | Monster bestanden mailen')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">LIMS / Rapportage</div><h1>Monster bestanden mailen: {{ $project->reference ?: $project->id }}</h1></div>
        <div class="flex flex-wrap gap-2">
            <a class="button" href="{{ route('projects.search', ['project' => $project->id]) }}">Terug naar project</a>
        </div>
    </div>
    <file-sender :config="{{ Illuminate\Support\Js::from($config) }}"></file-sender>
@endsection
