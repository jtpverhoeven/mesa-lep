@extends('layouts.app')
@section('title', 'MESA | Portaalanalyse toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Klantenportaal</div><h1>Portaalanalyse toevoegen</h1></div><a class="button secondary" href="{{ route('portal-assays.index') }}">Terug naar overzicht</a></div>
    <portal-assay-form v-bind="{{ Illuminate\Support\Js::from(['formAction' => route('portal-assays.store'), 'indexUrl' => route('portal-assays.index'), 'csrf' => csrf_token(), 'addToAllClients' => true]) }}"></portal-assay-form>
@endsection