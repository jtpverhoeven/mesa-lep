@extends('layouts.app')
@section('title', 'MESA | Rapportsjablonen')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Rapportage</div><h1>Rapportsjablonen</h1></div>
        <a class="button primary" href="{{ route('docgen-templates.create') }}">Sjabloon toevoegen</a>
    </div>    
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Naam</th><th>Map</th><th>Volgorde</th><th>Actief</th><th>PDF-uitvoer</th><th>Analyses</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td>{{ $template->id }}</td><td><strong>{{ $template->name }}</strong></td><td>{{ $template->folder }}</td><td>{{ $template->view_order ?? '-' }}</td>
                        <td>{{ $template->active ? 'Ja' : 'Nee' }}</td><td>{{ $template->isSupported() ? 'Beschikbaar' : 'Layout niet gevonden' }}</td><td>{{ $template->associations_count }}</td>
                        <td>
                            <a class="text-link" href="{{ route('docgen-templates.edit', $template) }}">Bewerken</a> 
                            <a class="text-link" href="{{ route('docgen-associations.edit', $template) }}">Analyse-associaties</a>
                            <form class="inline-form" method="POST" action="{{ route('docgen-templates.destroy', $template) }}" onsubmit="return confirm('Dit rapportsjabloon en de analyse-associaties verwijderen?');">
                                @csrf @method('DELETE')<button class="text-button" type="submit">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">Geen rapportsjablonen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
