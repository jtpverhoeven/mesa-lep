@extends('layouts.app')
@section('title', 'MESA | Analyse bewerken')
@php
    $selectedMediaInput = old('media', $selectedMedia);
    $selectedMatricesInput = old('matrices', $selectedMatrices);
    $selectedMetaAssaysInput = old('meta_assays', $selectedMetaAssays);
    $currentType = (int) old('type', $assay->type);
    $currentStartAnchor = old('start_anchor', $startAnchor);
@endphp
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Analyses</div><h1>Analyse bewerken</h1></div>
        <div class="heading-actions">
            <label class="revision-picker" for="revision_selector">Revisie
                <select id="revision_selector">
                    @foreach($revisions as $revision)
                        <option value="{{ $revision->id }}" @selected($revision->id === $assay->id)>Revisie {{ $revisions->count() - $loop->index }}@if($loop->first) (actueel)@endif</option>
                    @endforeach
                </select>
            </label>
            <a class="button" href="{{ route('assays.index') }}">Terug naar overzicht</a>
        </div>
    </div>

    @if(!$isTip)
        <div class="notice">Dit is een oudere revisie. Alleen de actuele revisie kan worden aangepast.</div>
    @endif

    <form method="POST" action="{{ route('assays.update', $assay) }}">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$isTip)>
            <input type="hidden" name="force_changes" value="0">
            <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
                <div class="field wide"><label for="name">Analysenaam</label><input id="name" name="name" value="{{ old('name', $assay->name) }}" maxlength="128" required autofocus></div>
                <div class="field"><label for="type_base">Analytisch basistype</label><select id="type_base" name="type_base" required><option value="">Selecteer basistype</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected(old('type_base', $assay->type_base) == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                <div class="field"><label for="type">Soort analyse</label><select id="type" name="type" required>@foreach([1 => 'Telling', 2 => 'Detectie', 3 => 'Overig', 4 => 'Meta'] as $value => $label)<option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>@endforeach</select></div>
            </div></fieldset>

            <fieldset class="form-section"><legend>Media en matrices</legend><div class="form-grid">
                <div class="field"><label>Gebruikte media / materialen</label><media-selector v-bind="{{ Illuminate\Support\Js::from(['media' => $media->map(fn ($medium) => ['id' => (string) $medium->id, 'name' => $medium->name, 'short_name' => $medium->short_name, 'active' => (int) $medium->active === 1])->values(), 'selected' => array_map('strval', (array) $selectedMediaInput), 'inputName' => 'media']) }}"></media-selector></div>
                <div class="field"><label>Matrices</label><div class="check-grid">@forelse($matrices as $matrix)<label><input type="checkbox" name="matrices[]" value="{{ $matrix->id }}" @checked(in_array($matrix->id, $selectedMatricesInput))>{{ $matrix->name }}</label>@empty<span class="muted">Geen matrices beschikbaar.</span>@endforelse</div></div>
            </div></fieldset>

            <fieldset class="form-section"><legend>Meta-analyse</legend><div class="check-grid">@forelse($assays as $metaAssay)<label><input type="checkbox" name="meta_assays[]" value="{{ $metaAssay->id }}" @checked(in_array($metaAssay->id, $selectedMetaAssaysInput))>{{ $metaAssay->name }}</label>@empty<span class="muted">Geen analyses beschikbaar.</span>@endforelse</div></fieldset>

            <fieldset class="form-section"><legend>Uitvoering</legend><div class="form-grid">
                <div class="field"><label for="min_count">Minimum telling</label><input type="number" id="min_count" name="min_count" min="0" max="2147483647" value="{{ old('min_count', $assay->min_count) }}" required></div>
                <div class="field"><label for="max_count">Maximum telling</label><input type="number" id="max_count" name="max_count" min="0" max="2147483647" value="{{ old('max_count', $assay->max_count) }}" required></div>
                <div class="field"><label for="duration">Analysetijd (dagen)</label><input id="duration" name="duration" inputmode="decimal" maxlength="5" value="{{ old('duration', $assay->duration) }}"></div>
                <div class="field"><label for="start_anchor">Start doorlooptijd</label><select id="start_anchor" name="start_anchor"><option value="r" @selected($currentStartAnchor === 'r')>Registratie</option><option value="i" @selected($currentStartAnchor === 'i')>Inzet</option><option value="p" @selected($currentStartAnchor === 'p')>Projectveld</option><option value="s" @selected($currentStartAnchor === 's')>Monsterveld</option></select></div>
                <div class="field" id="start_field_name_wrapper"><label for="start_field_name">Startveld</label><input id="start_field_name" name="start_field_name" value="{{ old('start_field_name', $startFieldName) }}" maxlength="126"></div>
                <div class="check-grid wide"><input type="hidden" name="dillution" value="0"><label><input type="checkbox" name="dillution" value="1" @checked(old('dillution', $assay->dillution))>Gebruikt verdunningen</label><input type="hidden" name="replicates" value="0"><label><input type="checkbox" name="replicates" value="1" @checked(old('replicates', $assay->replicates))>Gebruikt replica's</label></div>
            </div></fieldset>

            <fieldset class="form-section"><legend>Bevestiging</legend><div class="form-grid">
                <div class="check-grid wide"><input type="hidden" name="confirmation" value="0"><label><input type="checkbox" id="confirmation" name="confirmation" value="1" @checked(old('confirmation', $assay->confirmation))>Gebruikt bevestiging</label></div>
                <div class="field"><label for="confirmation_type">Type bevestiging</label><select id="confirmation_type" name="confirmation_type"><option value="0" @selected(old('confirmation_type', $assay->confirmation_type) == 0)>Een bevestiging voor alles</option><option value="1" @selected(old('confirmation_type', $assay->confirmation_type) == 1)>Een bevestiging per plaat</option></select></div>
                <div class="field"><label for="confirmation_init">Start bevestiging</label><select id="confirmation_init" name="confirmation_init"><option value="0" @selected(old('confirmation_init', $assay->confirmation_init) == 0)>Ja</option><option value="1" @selected(old('confirmation_init', $assay->confirmation_init) == 1)>Nee, standaard aan</option><option value="2" @selected(old('confirmation_init', $assay->confirmation_init) == 2)>Nee, standaard uit</option></select></div>
                <div class="field"><label for="confirmation_depth">Standaard aantal bevestigingen</label><input type="number" id="confirmation_depth" name="confirmation_depth" min="0" value="{{ old('confirmation_depth', $assay->confirmation_depth) }}"></div>
                <div class="field"><label for="show_conf_table">Bevestigingstabel</label><input id="show_conf_table" name="show_conf_table" value="{{ old('show_conf_table', $assay->show_conf_table) }}"></div>
                <div class="field wide"><label for="confirmation_script">Bevestigingsscript</label><textarea id="confirmation_script" name="confirmation_script" rows="4">{{ old('confirmation_script', $assay->confirmation_script) }}</textarea></div>
                <div class="field wide"><label for="confirmation_support">Bevestigingsondersteuning</label><textarea id="confirmation_support" name="confirmation_support" rows="3">{{ old('confirmation_support', $assay->confirmation_support) }}</textarea></div>
            </div></fieldset>

            <fieldset class="form-section"><legend>Rapportage en facturatie</legend><div class="form-grid">
                <div class="field"><label for="article_code">Artikelcode</label><input id="article_code" name="article_code" value="{{ old('article_code', $assay->article_code) }}"></div>
                <div class="check-grid"><input type="hidden" name="hide_report" value="0"><label><input type="checkbox" name="hide_report" value="1" @checked(old('hide_report', $assay->hide_report))>Verbergen op rapport</label><input type="hidden" name="uses_indicator" value="0"><label><input type="checkbox" name="uses_indicator" value="1" @checked(old('uses_indicator', $assay->uses_indicator))>Indicatoren op rapport</label><input type="hidden" name="uses_trip_indicator" value="0"><label><input type="checkbox" name="uses_trip_indicator" value="1" @checked(old('uses_trip_indicator', $assay->uses_trip_indicator))>Rode indicator op rapport</label><input type="hidden" name="billable" value="0"><label><input type="checkbox" name="billable" value="1" @checked(old('billable', $assay->billable))>Op facturatierapport</label></div>
            </div></fieldset>

            @if($fields->isNotEmpty())
                <fieldset class="form-section"><legend>Aanvullende velden</legend><div class="form-grid">@foreach($fields as $field)<div class="field"><label for="custom_{{ $field->id }}">{{ $field->name }}</label><input id="custom_{{ $field->id }}" name="custom_fields[{{ $field->id }}]" value="{{ old('custom_fields.'.$field->id, $customValues[$field->name] ?? $field->standard_value) }}" maxlength="1000"></div>@endforeach</div></fieldset>
            @endif

            <fieldset class="form-section"><legend>Berekening</legend><div class="field"><label for="script">MesaScript</label><textarea id="script" name="script" rows="7" maxlength="60000" spellcheck="false">{{ old('script', $assay->script) }}</textarea></div></fieldset>
        </fieldset>
        <div class="form-actions">@if($isTip)<button class="button primary" type="submit">Nieuwe revisie opslaan</button>@endif<a class="button" href="{{ route('assays.index') }}">Annuleren</a></div>
    </form>

    <script>
        document.getElementById('revision_selector')?.addEventListener('change', function () {
            window.location.href = '{{ url('/beheer/assays') }}/' + this.value + '/edit';
        });
        const startAnchor = document.getElementById('start_anchor');
        const startFieldNameWrapper = document.getElementById('start_field_name_wrapper');
        const syncStartField = () => {
            startFieldNameWrapper.hidden = ['p', 's'].indexOf(startAnchor.value) === -1;
        };
        startAnchor?.addEventListener('change', syncStartField);
        syncStartField();
    </script>
@endsection