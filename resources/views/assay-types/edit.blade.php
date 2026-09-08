@extends('layouts.app')
@section('title', 'MESA | Basistype bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytische basistypen</div><h1>Basistype bewerken</h1></div><a class="button" href="{{ route('assay-types.index') }}">Terug naar overzicht</a></div>

    <form method="POST" action="{{ route('assay-types.update', $type) }}">
        @csrf
        @method('PUT')
        <fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
            <div class="field"><label for="name">Naam</label><input id="name" name="name" required maxlength="128" value="{{ old('name', $type->name) }}" autofocus></div>
            <div class="field"><label for="description">Omschrijving</label><textarea id="description" name="description" maxlength="10000">{{ old('description', $type->description) }}</textarea></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Basistype opslaan</button><a class="button" href="{{ route('assay-types.index') }}">Annuleren</a></div>
    </form>

    <fieldset class="form-section"><legend>Resultaatvelden</legend>
        <div class="table-scroll"><table class="data-table"><thead><tr><th>ID</th><th>Naam</th><th>Alias</th><th>Type</th><th>Positie</th><th>Eindresultaat</th><th>Filter</th><th>Acties</th></tr></thead><tbody>
            @forelse($type->fields as $field)
                <tr><td>{{ $field->id }}</td><td><strong>{{ $field->name }}</strong></td><td>{{ $field->alias }}</td><td>{{ $field->type }}</td><td>{{ $field->pos }}</td><td>{{ $field->endresults_driver ? 'Ja' : 'Nee' }}</td><td>{{ [0 => 'Geen filter', 1 => 'Cijfers', 2 => 'Alfanumeriek', 3 => 'Letters', 4 => '+ / -'][$field->filter ?? 0] }}</td><td><a class="text-link" href="{{ route('assay-type-fields.edit', [$type, $field]) }}">Bewerken</a><form class="inline-form" method="POST" action="{{ route('assay-type-fields.destroy', [$type, $field]) }}">@csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button></form></td></tr>
            @empty
                <tr><td colspan="8" class="empty-state">Geen resultaatvelden gevonden.</td></tr>
            @endforelse
        </tbody></table></div>
    </fieldset>

    <form method="POST" action="{{ route('assay-type-fields.store', $type) }}">
        @csrf
        <fieldset class="form-section"><legend>Resultaatveld toevoegen</legend><div class="form-grid">
            <div class="field"><label for="field_name">Naam</label><input id="field_name" name="name" required maxlength="32" pattern="[A-Za-z0-9]+" value="{{ old('name') }}"><span class="muted">Gebruik alleen letters en cijfers.</span></div>
            <div class="field"><label for="field_alias">Alias</label><input id="field_alias" name="alias" required maxlength="64" value="{{ old('alias') }}"></div>
            <div class="field"><label for="field_type">Type</label><select id="field_type" name="type" required><option value="varchar" @selected(old('type', 'varchar') === 'varchar')>varchar</option><option value="int" @selected(old('type') === 'int')>int</option><option value="float" @selected(old('type') === 'float')>float</option></select></div>
            <div class="field"><label for="field_pos">Positie</label><input type="number" id="field_pos" name="pos" min="0" required value="{{ old('pos', 0) }}"></div>
            <div class="check-grid"><input type="hidden" name="endresults_driver" value="0"><label><input type="checkbox" name="endresults_driver" value="1" @checked(old('endresults_driver', 1))>Deel van eindresultaat</label></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Resultaatveld toevoegen</button></div>
    </form>
@endsection