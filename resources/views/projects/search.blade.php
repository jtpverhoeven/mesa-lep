@extends('layouts.app')
@section('title', 'MESA | Projecten zoeken')
@section('content')
    {{-- <div class="page-heading"><div><div class="eyebrow">Laboratorium / Projecten</div><h1>Projecten zoeken</h1></div></div> --}}
    <project-search v-bind="{{ Illuminate\Support\Js::from([
        'initialProjectId' => $initialProjectId,
        'endpoints' => [
            'page' => route('projects.search'),
            'search' => route('projects.search.data'),
            'revisions' => route('revisions.index'),
            'project' => route('projects.search.show', ['project' => '__PROJECT__']),
            'authorization' => route('projects.authorization.store', ['project' => '__PROJECT__']),
            'sample' => route('projects.search.samples.show', ['project' => '__PROJECT__', 'sample' => '__SAMPLE__']),
            'sampleLookup' => route('samples.lookup', ['barcode' => '__BARCODE__']),
            'metadata' => route('samples.metadata.store', ['sample' => '__SAMPLE__']),
            'results' => route('projects.search.samples.results.show', [
                'project' => '__PROJECT__',
                'sample' => '__SAMPLE__',
                'analysis' => '__ANALYSIS__',
            ]),
        ],
        'permissions' => $permissions,
    ]) }}"></project-search>
@endsection