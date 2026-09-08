@extends('layouts.app')
@section('title', 'MESA | Gebruikersgroep bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Gebruikersgroepen</div><h1>{{ $group->name }}</h1></div><a class="button" href="{{ route('user-groups.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('user-groups.update', $group) }}">
        @csrf @method('PUT')
        @include('user-groups._form')
        <div class="form-actions"><button class="button primary" type="submit">Wijzigingen opslaan</button><a class="button" href="{{ route('user-groups.index') }}">Annuleren</a></div>
    </form>
@endsection