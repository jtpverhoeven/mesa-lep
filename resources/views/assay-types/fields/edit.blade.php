@extends('layouts.app')
@section('title', 'MESA | Resultaatveld bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / {{ $type->name }}</div><h1>Resultaatveld bewerken</h1></div><a class="button" href="{{ route('assay-types.edit', $type) }}">Terug naar basistype</a></div>
    <form method="POST" action="{{ route('assay-type-fields.update', [$type, $field]) }}">
        @csrf
        @method('PUT')
        <fieldset class="form-section"><legend>Veldconfiguratie</legend><div class="form-grid">
            <div class="field"><label>Naam</label><input value="{{ $field->name }}" disabled></div>
            <div class="field"><label>Type</label><input value="{{ $field->type }}" disabled></div>
            <div class="field"><label for="alias">Alias</label><input id="alias" name="alias" required maxlength="64" value="{{ old('alias', $field->alias) }}" autofocus></div>
            <div class="field"><label for="pos">Positie</label><input type="number" id="pos" name="pos" min="0" required value="{{ old('pos', $field->pos) }}"></div>
            <div class="field"><label for="filter">Invoerfilter</label><select id="filter" name="filter" required><option value="0" @selected(old('filter', $field->filter ?? 0) == 0)>Geen filter</option><option value="1" @selected(old('filter', $field->filter ?? 0) == 1)>Enkel cijfers</option><option value="2" @selected(old('filter', $field->filter ?? 0) == 2)>Alfanumeriek</option><option value="3" @selected(old('filter', $field->filter ?? 0) == 3)>Enkel letters</option><option value="4" @selected(old('filter', $field->filter ?? 0) == 4)>Enkel + of -</option></select></div>
            <div class="check-grid"><input type="hidden" name="endresults_driver" value="0"><label><input type="checkbox" name="endresults_driver" value="1" @checked(old('endresults_driver', $field->endresults_driver))>Deel van eindresultaat</label></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Resultaatveld opslaan</button><a class="button" href="{{ route('assay-types.edit', $type) }}">Annuleren</a></div>
    </form>
@endsection