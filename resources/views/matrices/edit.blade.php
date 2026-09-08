@extends('layouts.app')
@section('title', 'MESA | Matrix bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Matrix bewerken</h1></div><a class="button" href="{{ route('matrices.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('matrices.update', $matrix) }}">
        @csrf
        @method('PUT')
        <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
            <div class="field wide"><label for="name">Naam</label><input id="name" name="name" value="{{ old('name', $matrix->name) }}" required autofocus></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Matrix opslaan</button><a class="button" href="{{ route('matrices.index') }}">Annuleren</a></div>
    </form>
@endsection