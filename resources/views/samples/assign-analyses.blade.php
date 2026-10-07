@extends('layouts.app')
@section('title', 'MESA | Analyses toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>Analyses toevoegen</h1></div></div>
    <sample-analysis-assignment v-bind="{{ Illuminate\Support\Js::from(['endpoints' => ['data' => route('samples.assign-analyses.data'), 'options' => route('samples.assign-analyses.options', ['client' => '__CLIENT__']), 'store' => route('samples.assign-analyses.store')]]) }}"></sample-analysis-assignment>
@endsection