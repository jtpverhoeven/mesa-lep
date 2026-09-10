@extends('layouts.app')
@section('title', 'MESA | Onderzoeksprofielen bulkwijziging')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Onderzoeksprofielen</div><h1>Bulkwijziging</h1></div><a class="button" href="{{ route('research-profiles.index') }}">Terug naar overzicht</a></div>
    <research-profile-bulk-editor v-bind="{{ Illuminate\Support\Js::from(['endpoints' => [
        'list' => route('research-profiles.list'),
        'data' => route('research-profiles.data'),
        'bulk' => route('research-profiles.bulk.update'),
    ]]) }}"></research-profile-bulk-editor>
@endsection