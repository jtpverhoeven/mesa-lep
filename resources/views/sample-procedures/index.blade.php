@extends('layouts.app')
@section('title', 'MESA | Bemonster procedures')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysestroom</div><h1>Bemonster procedures</h1></div><div class="heading-actions"><a class="button" href="{{ route('sample-procedures.defaults') }}">Standaardprocedures</a><a class="button primary" href="{{ route('sample-procedures.create') }}">Bemonsterprocedure toevoegen</a></div></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>ID</th><th>Naam</th><th>Rapport</th><th>Status</th><th>Acties</th></tr></thead><tbody>
        @forelse($procedures as $procedure)
            <tr @class(['muted' => ! $procedure->active])><td>{{ $procedure->id }}</td><td><strong>{{ $procedure->name }}</strong></td><td>{{ $procedure->hide ? 'Verborgen' : 'Zichtbaar' }}</td><td>{{ $procedure->active ? 'Actief' : 'Gedeactiveerd' }}</td><td>@if($procedure->active)<a class="text-link" href="{{ route('sample-procedures.edit', $procedure) }}">Bewerken</a><form class="inline-form" method="POST" action="{{ route('sample-procedures.destroy', $procedure) }}" onsubmit="return confirm('Deze bemonsterprocedure deactiveren?');">@csrf @method('DELETE')<button class="text-button" type="submit">Deactiveren</button></form>@endif</td></tr>
        @empty
            <tr><td colspan="5" class="empty-state">Geen bemonster procedures gevonden.</td></tr>
        @endforelse
    </tbody></table></div>
@endsection