@extends('layouts.app')
@section('title', 'MESA | Klantbeschikbaarheid')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Klantenportaal</div><h1>Klantbeschikbaarheid</h1><p>{{ $portalAssay->common_name }}</p></div><a class="button secondary" href="{{ route('portal-assays.edit', $portalAssay) }}">Terug naar portaalanalyse</a></div>
    <portal-assay-clients v-bind="{{ Illuminate\Support\Js::from(['clients' => $clients, 'selectedClientIds' => $selectedClientIds, 'copySources' => $copySources, 'formAction' => route('portal-assays.clients.update', $portalAssay), 'copyAction' => route('portal-assays.clients.copy', $portalAssay), 'csrf' => csrf_token()]) }}"></portal-assay-clients>
@endsection