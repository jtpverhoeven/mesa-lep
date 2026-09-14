@extends('layouts.app')
@section('title', 'MESA | Analyses')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Analyses</h1></div><a class="button primary" href="{{ route('assays.create') }}">Analyse toevoegen</a></div>
    <assay-directory v-bind="{{ Illuminate\Support\Js::from(['assays' => $assays, 'editUrl' => route('assays.edit', ['assay' => '__ASSAY__'])]) }}"></assay-directory>
@endsection