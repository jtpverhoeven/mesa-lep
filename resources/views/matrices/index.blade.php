@extends('layouts.app')
@section('title', 'MESA | Matrices')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Matrices</h1></div><a class="button primary" href="{{ route('matrices.create') }}">Matrix toevoegen</a></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">ID</th><th scope="col">Naam</th><th scope="col">Acties</th></tr></thead><tbody>
        @forelse($matrices as $matrix)
            <tr><td>{{ $matrix->id }}</td><td><strong>{{ $matrix->name }}</strong></td><td><a class="text-link" href="{{ route('matrices.edit', $matrix) }}">Bewerken</a><form class="inline-form" method="POST" action="{{ route('matrices.destroy', $matrix) }}" onsubmit="return confirm('Deze matrix verwijderen?');">@csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button></form></td></tr>
        @empty
            <tr><td colspan="3" class="empty-state">Geen matrices gevonden.</td></tr>
        @endforelse
    </tbody></table></div>
@endsection