@extends('layouts.app')
@section('title', 'MESA | Gebruikersgroepen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Gebruikers &amp; groepen</div><h1>Gebruikersgroepen</h1></div><a class="button primary" href="{{ route('user-groups.create') }}">Groep toevoegen</a></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">Groep</th><th scope="col">Groepsleider</th><th scope="col">Gebruikers</th><th scope="col">Supergroep</th><th scope="col">Acties</th></tr></thead>
            <tbody>
                @forelse($groups as $group)
                    <tr><td><strong>{{ $group->name }}</strong></td><td>{{ $group->leader?->name ?? '-' }}</td><td>{{ $group->users_count }}</td><td>{{ $group->superGroup ? 'Ja' : 'Nee' }}</td><td><a class="text-link" href="{{ route('user-groups.edit', $group) }}">Bewerken</a>@if($group->name !== 'administrator')<form class="inline-form" method="POST" action="{{ route('user-groups.destroy', $group) }}" onsubmit="return confirm('Deze gebruikersgroep verwijderen?')">@csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button></form>@endif</td></tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Geen gebruikersgroepen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection