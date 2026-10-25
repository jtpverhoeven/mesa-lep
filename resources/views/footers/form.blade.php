@extends('layouts.app')
@section('title', 'MESA | Rapportvoettekst')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Rapportage</div><h1>Voettekst {{ $footer->exists ? 'bewerken' : 'toevoegen' }}</h1></div>
        <a class="button" href="{{ route('footers.index') }}">Terug naar overzicht</a>
    </div>
    <form method="POST" action="{{ $footer->exists ? route('footers.update', $footer) : route('footers.store') }}">
        @csrf
        @if($footer->exists) @method('PUT') @endif
        <fieldset class="form-section">
            <legend>Certificaatinformatie</legend>
            <div class="form-grid">
                <div class="field"><label for="name">Sjabloonmap</label><input id="name" name="name" list="template-folders" value="{{ old('name', $footer->name) }}" required autofocus><datalist id="template-folders">@foreach($folders as $folder)<option value="{{ $folder }}">@endforeach</datalist></div>
                <div class="field"><label for="lang">Taal</label><select id="lang" name="lang"><option value="nl" @selected(old('lang', $footer->lang) === 'nl')>Nederlands</option><option value="en" @selected(old('lang', $footer->lang) === 'en')>Engels</option></select></div>
                <div class="field"><label for="q">In RvA-scope?</label><select id="q" name="q"><option value="0" @selected(! old('q', $footer->q))>Nee</option><option value="1" @selected(old('q', $footer->q))>Ja</option></select></div>
            </div>
            <div class="field mt-4">
                <label for="data">Voettekst</label>
                <textarea id="data" name="data" rows="14" required>{{ old('data', $footer->data) }}</textarea>
                <small class="text-muted">Neem de goedgekeurde tekst letterlijk over. Tekstopmaak en tabellen zijn toegestaan; scripts en externe inhoud worden niet in de PDF opgenomen.</small>
            </div>
        </fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Voettekst opslaan</button><a class="button" href="{{ route('footers.index') }}">Annuleren</a></div>
    </form>
@endsection
