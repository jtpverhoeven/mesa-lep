@extends('layouts.app')
@section('title', 'MESA | Projecten zoeken')
@section('content')
    {{-- <div class="page-heading"><div><div class="eyebrow">Laboratorium / Projecten</div><h1>Projecten zoeken</h1></div></div> --}}
    <project-search v-bind="{{ Illuminate\Support\Js::from([
        'endpoints' => [
            'search' => route('projects.search.data'),
            'project' => route('projects.search.show', ['project' => '__PROJECT__']),
            'sample' => route('projects.search.samples.show', ['project' => '__PROJECT__', 'sample' => '__SAMPLE__']),
            'results' => route('projects.search.samples.results.show', [
                'project' => '__PROJECT__',
                'sample' => '__SAMPLE__',
                'analysis' => '__ANALYSIS__',
            ]),
        ],
        'permissions' => $permissions,
    ]) }}"></project-search>
@endsection