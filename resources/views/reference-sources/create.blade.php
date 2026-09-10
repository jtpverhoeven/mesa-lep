@extends('layouts.app')
@section('title', 'MESA | Referentiebron toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Referentiebron toevoegen</h1></div><a class="button" href="{{ route('reference-sources.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('reference-sources.store') }}">
        @csrf
        <fieldset class="form-section"><legend>Referentiebron</legend><div class="form-grid">
            <div class="field"><label for="name_nl">Naam NL</label><input id="name_nl" name="name_nl" value="{{ old('name_nl') }}" required autofocus></div>
            <div class="field"><label for="name_en">Naam ENG</label><input id="name_en" name="name_en" value="{{ old('name_en') }}"></div>
            <div class="field"><label for="client">Toepassing</label><select id="client" name="client"><option value="">Globaal</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client') == $client->id)>{{ $client->name }} ({{ $client->id }})</option>@endforeach</select></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Referentiebron aanmaken</button><a class="button" href="{{ route('reference-sources.index') }}">Annuleren</a></div>
    </form>
@endsection