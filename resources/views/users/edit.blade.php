@extends('layouts.app')
@section('title', 'MESA | Gebruiker bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Gebruikers</div><h1>{{ $editedUser->name }}</h1></div><a class="button" href="{{ route('users.index') }}">Terug naar overzicht</a></div>
    @unless($editedUser->enabled)<div class="notice error">Dit account is geblokkeerd en kan niet aanmelden.</div>@endunless
    <form method="POST" action="{{ route('users.update', $editedUser) }}">
        @csrf @method('PUT')
        @include('users._form')
        <div class="form-actions"><button class="button primary" type="submit">Wijzigingen opslaan</button><a class="button" href="{{ route('users.index') }}">Annuleren</a></div>
    </form>
@endsection