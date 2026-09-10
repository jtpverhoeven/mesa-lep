@extends('layouts.app')
@section('title', 'MESA | Monster aanmelden')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>Monster aanmelden</h1></div></div>
    <sample-create-form v-bind="{{ Illuminate\Support\Js::from(['endpoints' => ['formData' => route('samples.form-data'), 'clientSearch' => route('samples.clients.search'), 'clientProjects' => route('samples.projects.index', ['client' => '__CLIENT__']), 'analysisOptions' => route('samples.analysis-options', ['client' => '__CLIENT__']), 'project' => route('samples.projects.show', ['project' => '__PROJECT__']), 'store' => route('samples.store')]]) }}"></sample-create-form>
@endsection