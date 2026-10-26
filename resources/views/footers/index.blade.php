@extends('layouts.app')
@section('title', 'MESA | Rapportvoetteksten')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Rapportage</div><h1>Rapportvoetteksten</h1></div>
        <a class="button primary" href="{{ route('footers.create') }}">Voettekst toevoegen</a>
    </div>
    
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Sjabloonmap</th><th>Taal</th><th>In RvA-scope?</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($footers as $footer)
                    <tr>
                        <td>{{ $footer->id }}</td><td>{{ $footer->name }}</td><td>{{ $footer->lang }}</td><td>{{ $footer->q ? 'Ja' : 'Nee' }}</td>
                        <td>
                            <a class="text-link" href="{{ route('footers.edit', $footer) }}">Bewerken</a>
                            <form class="inline-form" method="POST" action="{{ route('footers.destroy', $footer) }}" onsubmit="return confirm('Deze voettekst verwijderen?');">
                                @csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Geen rapportvoetteksten gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
