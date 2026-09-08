@extends('layouts.app')
@section('title', 'MESA | Analyseveld bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysevelden</div><h1>Analyseveld bewerken</h1></div><a class="button" href="{{ route('assay-fields.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('assay-fields.update', $assayField) }}">
        @csrf
        @method('PUT')
        <fieldset class="form-section"><legend>Analyseveld</legend><div class="form-grid">
            <div class="field"><label for="name">Veldnaam</label><input id="name" name="name" value="{{ old('name', $assayField->name) }}" maxlength="64" required autofocus></div>
            @php($hasDefaultValue = old('has_default_value', $assayField->standard_value !== null))
            <div class="field wide"><input type="hidden" name="has_default_value" value="0"><label><input type="checkbox" id="has_default_value" name="has_default_value" value="1" @checked($hasDefaultValue)> Dit veld heeft een standaardwaarde</label></div>
            <div class="field" id="standard_value_wrapper" @if(!$hasDefaultValue) hidden @endif><label for="standard_value">Standaardwaarde</label><input id="standard_value" name="standard_value" value="{{ old('standard_value', $assayField->standard_value) }}" maxlength="64"></div>
            <div class="field"><label for="position">Positie</label><input type="number" id="position" name="position" min="0" value="{{ old('position', $assayField->position) }}" required></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('assay-fields.index') }}">Annuleren</a></div>
    </form>
    <script>
        const defaultValueCheckbox = document.getElementById('has_default_value');
        const standardValueWrapper = document.getElementById('standard_value_wrapper');
        const syncDefaultValueVisibility = () => {
            standardValueWrapper.hidden = !defaultValueCheckbox.checked;
        };
        defaultValueCheckbox?.addEventListener('change', syncDefaultValueVisibility);
        syncDefaultValueVisibility();
    </script>
@endsection