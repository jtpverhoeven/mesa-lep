@extends('layouts.app')
@section('title', 'MESA | Analytische basistypen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Analytische basistypen</h1></div><a class="button" href="{{ route('assay-types.create') }}">Basistype toevoegen</a></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">Naam</th><th scope="col">Omschrijving</th><th scope="col">Acties</th></tr></thead><tbody>
        @forelse($types as $type)<tr><td><strong>{{ $type->name }}</strong></td><td style="white-space:normal">{{ $type->description }}</td><td><a class="text-link" href="{{ route('assay-types.edit', $type) }}">Bewerken</a></td></tr>@empty<tr><td colspan="3" class="empty-state">Geen analytische basistypen gevonden.</td></tr>@endforelse
    </tbody></table></div>
@endsection