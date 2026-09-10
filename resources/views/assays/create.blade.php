@extends('layouts.app')
@section('title', 'MESA | Analyse toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analyses</div><h1>Analyse toevoegen</h1></div><a class="button" href="{{ route('assays.index') }}">Terug naar overzicht</a></div>
    @if($types->isEmpty())<div class="notice">Geen analytisch basistype beschikbaar. <a class="text-link" href="{{ route('assay-types.create') }}">Basistype toevoegen</a></div>@endif
    <form method="POST" action="{{ route('assays.store') }}">
        @csrf
        <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
            <div class="field wide"><label for="name">Analysenaam</label><input id="name" name="name" value="{{ old('name') }}" maxlength="128" required autofocus></div>
            <div class="field"><label for="type_base">Analytisch basistype</label><select id="type_base" name="type_base" required><option value="">Selecteer basistype</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected(old('type_base') == $type->id)>{{ $type->name }}</option>@endforeach</select><a class="text-link" href="{{ route('assay-types.create') }}">Basistype toevoegen</a></div>
            <div class="field"><label for="type">Soort analyse</label><select id="type" name="type" required>@foreach([1 => 'Telling', 2 => 'Detectie', 3 => 'Overig', 4 => 'Meta'] as $value => $label)<option value="{{ $value }}" @selected(old('type', 1) == $value)>{{ $label }}</option>@endforeach</select></div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Uitvoering</legend><div class="form-grid">
            <div class="field"><label for="min_count">Minimum telling</label><input type="number" id="min_count" name="min_count" min="0" max="2147483647" value="{{ old('min_count', 0) }}" required></div>
            <div class="field"><label for="max_count">Maximum telling</label><input type="number" id="max_count" name="max_count" min="0" max="2147483647" value="{{ old('max_count', 300) }}" required></div>
            <div class="field"><label for="duration">Analysetijd (dagen)</label><input id="duration" name="duration" inputmode="decimal" maxlength="5" value="{{ old('duration') }}"></div>
            <div class="field"><label for="start_from">Start doorlooptijd</label><select id="start_from" name="start_from">@foreach(['r' => 'Registratie', 'i' => 'Inzet'] as $value => $label)<option value="{{ $value }}" @selected(old('start_from', 'r') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="check-grid wide">@foreach(['dillution' => 'Gebruikt verdunningen', 'replicates' => "Gebruikt replica's"] as $name => $label)<input type="hidden" name="{{ $name }}" value="0"><label><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, 0))>{{ $label }}</label>@endforeach</div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Media en matrices</legend><div class="form-grid">
            <div class="field"><label>Gebruikte media / materialen</label><media-selector v-bind="{{ Illuminate\Support\Js::from(['media' => $media->map(fn ($medium) => ['id' => (string) $medium->id, 'name' => $medium->name, 'short_name' => $medium->short_name, 'active' => true])->values(), 'selected' => array_map('strval', (array) old('media', [])), 'inputName' => 'media']) }}"></media-selector></div>
            <div class="field"><label>Matrices</label><div class="check-grid">@forelse($matrices as $matrix)<label><input type="checkbox" name="matrices[]" value="{{ $matrix->id }}" @checked(in_array($matrix->id, old('matrices', [])))>{{ $matrix->name }}</label>@empty<span class="muted">Geen matrices beschikbaar.</span>@endforelse</div></div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Meta-analyse</legend><div class="check-grid">
            @forelse($assays as $assay)<label><input type="checkbox" name="meta_assays[]" value="{{ $assay->id }}" @checked(in_array($assay->id, old('meta_assays', [])))>{{ $assay->name }}</label>@empty<span class="muted">Geen analyses beschikbaar.</span>@endforelse
        </div></fieldset>
        <fieldset class="form-section"><legend>Rapportage en facturatie</legend><div class="form-grid">
            <div class="field"><label for="article_code">Artikelcode</label><input id="article_code" name="article_code" value="{{ old('article_code') }}" maxlength="255"></div>
            <div class="check-grid">@foreach(['hide_report' => ['Verbergen op rapport', 0], 'uses_indicator' => ['Indicatoren op rapport', 1], 'uses_trip_indicator' => ['Rode indicator op rapport', 1], 'billable' => ['Opnemen in facturatierapport', 1]] as $name => [$label, $default])<input type="hidden" name="{{ $name }}" value="0"><label><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $default))>{{ $label }}</label>@endforeach</div>
        </div></fieldset>
        @if($fields->isNotEmpty())
            <fieldset class="form-section"><legend>Aanvullende velden</legend><div class="form-grid">@foreach($fields as $field)<div class="field"><label for="custom_{{ $field->id }}">{{ $field->name }}</label><input id="custom_{{ $field->id }}" name="custom_fields[{{ $field->id }}]" value="{{ old('custom_fields.'.$field->id, $field->standard_value) }}" maxlength="1000"></div>@endforeach</div></fieldset>
        @endif
        <fieldset class="form-section"><legend>Berekening</legend><div class="field"><label for="script">MesaScript</label><textarea id="script" name="script" rows="6" maxlength="60000" spellcheck="false">{{ old('script') }}</textarea></div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit" @disabled($types->isEmpty())>Analyse aanmaken</button><a class="button" href="{{ route('assays.index') }}">Annuleren</a></div>
    </form>
@endsection