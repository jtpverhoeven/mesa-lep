@extends('layouts.app')
@section('title', 'MESA | Klantcategorie toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Klanten</div><h1>Klantcategorie toevoegen</h1></div><a class="button" href="{{ route('client-categories.index') }}">Terug naar overzicht</a></div>
    <client-category-form v-bind="{{ Illuminate\Support\Js::from(['category' => old(), 'action' => route('client-categories.store'), 'cancelUrl' => route('client-categories.index'), 'csrf' => csrf_token()]) }}"></client-category-form>
@endsection