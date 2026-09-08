@extends('layouts.app')
@section('title', 'MESA | Gebruiker toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Gebruikers</div><h1>Gebruiker toevoegen</h1></div><a class="button" href="{{ route('users.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form')
        <div class="form-actions"><button class="button primary" type="submit">Gebruiker aanmaken</button><a class="button" href="{{ route('users.index') }}">Annuleren</a></div>
    </form>
@endsection