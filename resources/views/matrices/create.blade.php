@extends('layouts.app')
@section('title', 'MESA | Matrix toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Matrix toevoegen</h1></div><a class="button" href="{{ route('matrices.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('matrices.store') }}">
        @csrf
        <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
            <div class="field wide"><label for="name">Naam</label><input id="name" name="name" value="{{ old('name') }}" required autofocus></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Matrix aanmaken</button><a class="button" href="{{ route('matrices.index') }}">Annuleren</a></div>
    </form>
@endsection