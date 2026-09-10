@extends('layouts.app')
@section('title', 'MESA | Klant toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Klanten</div><h1>Klant toevoegen</h1></div><a class="button" href="{{ route('clients.index') }}">Terug naar overzicht</a></div>
    <client-form v-bind="{{ Illuminate\Support\Js::from(['client' => array_merge(['categories' => []], old()), 'categories' => $categories, 'action' => route('clients.store'), 'method' => 'POST', 'submitLabel' => 'Klant aanmaken', 'cancelUrl' => route('clients.index'), 'csrf' => csrf_token()]) }}"></client-form>
@endsection