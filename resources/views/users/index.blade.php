@extends('layouts.app')
@section('title', 'MESA | Gebruikers')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Gebruikers &amp; groepen</div><h1>Gebruikers</h1></div><a class="button primary" href="{{ route('users.create') }}">Gebruiker toevoegen</a></div>
    <form method="GET" action="{{ route('users.index') }}" class="form-actions">
        <div class="field" style="width:min(420px,100%)"><label for="search">Zoeken</label><input id="search" name="search" value="{{ $search }}" placeholder="Naam, gebruikersnaam of e-mailadres"></div>
        <button class="button" type="submit">Zoeken</button>
        @if($search)<a class="button" href="{{ route('users.index') }}">Wissen</a>@endif
    </form>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">Gebruikersnaam</th><th scope="col">Naam</th><th scope="col">E-mail</th><th scope="col">Groepen</th><th scope="col">Actief</th><th scope="col">Acties</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    <tr><td><strong>{{ $user->profile?->username ?? '-' }}</strong></td><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td><td>{{ $user->enabled ? 'Ja' : 'Nee' }}</td><td><a class="text-link" href="{{ route('users.edit', $user) }}">Bewerken</a></td></tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Geen gebruikers gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px">{{ $users->links() }}</div>
@endsection