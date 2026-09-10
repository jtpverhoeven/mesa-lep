@extends('layouts.app')
@section('title', 'MESA | Klant bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Klanten</div><h1>{{ $client->name }} bewerken</h1></div><a class="button" href="{{ route('clients.index') }}">Terug naar overzicht</a></div>
    <client-form v-bind="{{ Illuminate\Support\Js::from(['client' => $client, 'categories' => $categories, 'action' => route('clients.update', $client), 'method' => 'PUT', 'submitLabel' => 'Wijzigingen opslaan', 'cancelUrl' => route('clients.index'), 'csrf' => csrf_token()]) }}"></client-form>
@endsection