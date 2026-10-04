@extends('layouts.app')
@section('title', 'MESA | Beschikbare analyses')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Klantenportaal</div><h1>Beschikbare analyses</h1></div><a class="button primary" href="{{ route('portal-assays.create') }}">Portaalanalyse toevoegen</a></div>
    <portal-assay-directory v-bind="{{ Illuminate\Support\Js::from(['portalAssays' => $portalAssays, 'editUrl' => route('portal-assays.edit', ['portalAssay' => '__PORTAL_ASSAY__']), 'clientsUrl' => route('portal-assays.clients', ['portalAssay' => '__PORTAL_ASSAY__'])]) }}"></portal-assay-directory>
@endsection