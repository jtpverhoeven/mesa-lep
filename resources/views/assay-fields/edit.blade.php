@extends('layouts.app')
@section('title', 'MESA | Analyseveld bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysevelden</div><h1>Analyseveld bewerken</h1></div><a class="button" href="{{ route('assay-fields.index') }}">Terug naar overzicht</a></div>
    <assay-field-form v-bind="{{ Illuminate\Support\Js::from(['assayField' => array_merge($assayField->toArray(), old()), 'action' => route('assay-fields.update', $assayField), 'method' => 'PUT', 'submitLabel' => 'Opslaan', 'cancelUrl' => route('assay-fields.index'), 'csrf' => csrf_token()]) }}"></assay-field-form>
@endsection