@extends('layouts.app')
@section('title', 'MESA | Analyseveld toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysevelden</div><h1>Analyseveld toevoegen</h1></div><a class="button" href="{{ route('assay-fields.index') }}">Terug naar overzicht</a></div>
    <assay-field-form v-bind="{{ Illuminate\Support\Js::from(['assayField' => array_merge(['has_default_value' => false, 'standard_value' => '', 'position' => 0], old()), 'action' => route('assay-fields.store'), 'method' => 'POST', 'submitLabel' => 'Analyseveld toevoegen', 'cancelUrl' => route('assay-fields.index'), 'csrf' => csrf_token()]) }}"></assay-field-form>
@endsection