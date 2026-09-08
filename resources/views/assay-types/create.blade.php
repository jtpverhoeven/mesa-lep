@extends('layouts.app')
@section('title', 'MESA | Basistype toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytische basistypen</div><h1>Basistype toevoegen</h1></div><a class="button" href="{{ route('assay-types.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('assay-types.store') }}">
        @csrf
        <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
            <div class="field"><label for="name">Naam</label><input id="name" name="name" required maxlength="128" value="{{ old('name') }}" autofocus></div>
            <div class="field"><label for="description">Omschrijving</label><textarea id="description" name="description" maxlength="10000">{{ old('description') }}</textarea></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Basistype aanmaken</button><a class="button" href="{{ route('assay-types.index') }}">Annuleren</a></div>
    </form>
@endsection