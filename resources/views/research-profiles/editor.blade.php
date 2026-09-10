@extends('layouts.app')
@section('title', 'MESA | Onderzoeksprofiel '.($researchProfile ? 'bewerken' : 'toevoegen'))
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Onderzoeksprofielen</div><h1>{{ $researchProfile ? 'Onderzoeksprofiel bewerken' : 'Onderzoeksprofiel toevoegen' }}</h1></div>
        <a class="button" href="{{ route('research-profiles.index') }}">Terug naar overzicht</a>
    </div>
    <research-profile-editor v-bind="{{ Illuminate\Support\Js::from(['endpoints' => [
        'data' => $researchProfile ? route('research-profiles.data', $researchProfile) : route('research-profiles.data'),
        'list' => route('research-profiles.list'),
        'referenceSources' => route('research-profiles.reference-sources'),
        'clients' => route('research-profiles.clients.search'),
        'save' => $researchProfile ? route('research-profiles.update', $researchProfile) : route('research-profiles.store'),
        'copy' => $researchProfile ? route('research-profiles.copy', $researchProfile) : null,
        'edit' => url('/admin/research-profiles/__ID__/edit'),
        'index' => route('research-profiles.index'),
    ]]) }}"></research-profile-editor>
@endsection