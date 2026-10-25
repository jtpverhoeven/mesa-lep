@extends('layouts.app')
@section('title', 'MESA | Rapportsjabloon')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Rapportage</div><h1>Sjabloon {{ $template->exists ? 'bewerken' : 'toevoegen' }}</h1></div>
        <a class="button" href="{{ route('docgen-templates.index') }}">Terug naar overzicht</a>
    </div>
    <form method="POST" action="{{ $template->exists ? route('docgen-templates.update', $template) : route('docgen-templates.store') }}">
        @csrf
        @if($template->exists) @method('PUT') @endif
        <fieldset class="form-section">
            <legend>Rapportsjabloon</legend>
            <div class="form-grid">
                <div class="field"><label for="name">Naam</label><input id="name" name="name" value="{{ old('name', $template->name) }}" required autofocus></div>
                <div class="field"><label for="folder">Map</label><input id="folder" name="folder" value="{{ old('folder', $template->folder) }}" required maxlength="128"><small class="text-muted">Beschikbare PDF-uitvoer: maz2020. De map bepaalt ook welke voettekst wordt gebruikt.</small></div>
                <div class="field"><label for="view_order">Volgorde</label><input id="view_order" name="view_order" type="number" min="0" value="{{ old('view_order', $template->view_order) }}"></div>
                <div class="field"><label for="active">Actief</label><select id="active" name="active"><option value="1" @selected(old('active', $template->active))>Ja</option><option value="0" @selected(! old('active', $template->active))>Nee</option></select></div>
            </div>
        </fieldset>
        <div class="form-actions">
            <button class="button primary" type="submit">Sjabloon opslaan</button>
            @if($template->exists)<a class="button" href="{{ route('docgen-associations.edit', $template) }}">Analyse-associaties</a>@endif
            <a class="button" href="{{ route('docgen-templates.index') }}">Annuleren</a>
        </div>
    </form>
@endsection
