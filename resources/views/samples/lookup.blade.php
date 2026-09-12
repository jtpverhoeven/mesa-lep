@extends('layouts.app')
@section('title', 'MESA | Monster opzoeken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>Monster opzoeken</h1></div></div>
    <sample-lookup v-bind="{{ Illuminate\Support\Js::from(['endpoints' => ['lookup' => route('samples.lookup.data'), 'options' => route('samples.lookup.options', ['sample' => '__SAMPLE__']), 'research' => route('samples.lookup.research', ['sample' => '__SAMPLE__']), 'results' => route('sample-analyses.results.index', ['sampleAnalysis' => '__ANALYSIS__']), 'updateResult' => route('sample-analyses.results.update', ['sampleAnalysis' => '__ANALYSIS__', 'result' => '__RESULT__'])], 'permissions' => ['add' => auth()->user()->can('samples.assign-research'), 'edit' => auth()->user()->can('samples.update-research')]]) }}"></sample-lookup>
@endsection