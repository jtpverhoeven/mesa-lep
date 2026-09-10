@extends('layouts.app')
@section('title', 'MESA | Referentie bronnen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Referentie bronnen</h1></div><a class="button primary" href="{{ route('reference-sources.create') }}">Referentiebron toevoegen</a></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">ID</th><th scope="col">Naam NL</th><th scope="col">Naam ENG</th><th scope="col">Toepassing</th><th scope="col">Acties</th></tr></thead><tbody>
        @forelse($referenceSources as $referenceSource)
            <tr><td>{{ $referenceSource->id }}</td><td><strong>{{ $referenceSource->localizedName('nl') ?: '-' }}</strong></td><td>{{ $referenceSource->name['en'] ?? '-' }}</td><td>{{ $referenceSource->clientRecord?->name ?? 'Globaal' }}</td><td><a class="text-link" href="{{ route('reference-sources.edit', $referenceSource) }}">Bewerken</a><form class="inline-form" method="POST" action="{{ route('reference-sources.destroy', $referenceSource) }}" onsubmit="return confirm('Deze referentiebron verwijderen?');">@csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button></form></td></tr>
        @empty
            <tr><td colspan="5" class="empty-state">Geen referentiebronnen gevonden.</td></tr>
        @endforelse
    </tbody></table></div>
@endsection