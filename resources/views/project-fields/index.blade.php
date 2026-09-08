@extends('layouts.app')
@section('title', 'MESA | Project velden')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Project velden</h1></div><a class="button primary" href="{{ route('project-fields.create') }}">Projectveld toevoegen</a></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">ID</th><th scope="col">Veldnaam</th><th scope="col">Weergavenaam</th><th scope="col">Type</th><th scope="col">Standaardwaarde</th><th scope="col">Positie</th><th scope="col"></th></tr></thead><tbody>
        @forelse($fields as $field)
            <tr><td>{{ $field->id }}</td><td><strong>{{ $field->name }}</strong></td><td>{{ $field->alias }}</td><td>{{ $field->type }}</td><td>{{ $field->std_value }}</td><td>{{ $field->position }}</td><td class="actions"><a class="text-link" href="{{ route('project-fields.edit', $field) }}">Bewerken</a><form class="inline-form" method="POST" action="{{ route('project-fields.destroy', $field) }}" onsubmit="return confirm('Dit projectveld verwijderen?');">@csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button></form></td></tr>
        @empty
            <tr><td colspan="7" class="muted">Geen projectvelden ingesteld.</td></tr>
        @endforelse
    </tbody></table></div>
@endsection