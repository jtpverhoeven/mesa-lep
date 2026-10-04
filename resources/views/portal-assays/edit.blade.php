@extends('layouts.app')
@section('title', 'MESA | '.$portalAssay->common_name)
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Klantenportaal</div><h1>{{ $portalAssay->common_name }}</h1></div><div class="page-actions"><a class="button secondary" href="{{ route('portal-assays.clients', $portalAssay) }}">Klantbeschikbaarheid</a><a class="button secondary" href="{{ route('portal-assays.index') }}">Terug naar overzicht</a></div></div>
    <portal-assay-form v-bind="{{ Illuminate\Support\Js::from(['portalAssay' => $portalAssay, 'assays' => $assays, 'selectedAssayIds' => $associatedAssayIds, 'showAssociations' => true, 'formAction' => route('portal-assays.update', $portalAssay), 'associationToggleAction' => route('portal-assays.assays.toggle', $portalAssay), 'indexUrl' => route('portal-assays.index'), 'csrf' => csrf_token()]) }}"></portal-assay-form>
@endsection