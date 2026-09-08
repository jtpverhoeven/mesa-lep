@extends('layouts.app')
@section('title', 'MESA | Analyses')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analytisch</div><h1>Analyses</h1></div><a class="button primary" href="{{ route('assays.create') }}">Analyse toevoegen</a></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">ID</th><th scope="col">Analyse</th><th scope="col">Analytisch basistype</th><th scope="col">Soort</th><th scope="col">Verdunning</th><th scope="col">Replica's</th><th scope="col">Artikelcode</th><th scope="col">Acties</th></tr></thead>
            <tbody>
                @forelse($assays as $assay)
                    <tr><td>{{ $assay->id }}</td><td><strong>{{ $assay->name }}</strong></td><td>{{ $assay->assayType?->name ?? '-' }}</td><td>{{ [1 => 'Telling', 2 => 'Detectie', 3 => 'Overig', 4 => 'Meta'][$assay->type] ?? $assay->type }}</td><td>{{ $assay->dillution ? 'Ja' : 'Nee' }}</td><td>{{ $assay->replicates ? 'Ja' : 'Nee' }}</td><td>{{ $assay->article_code ?: '-' }}</td><td><a class="text-link" href="{{ route('assays.edit', $assay) }}">Bewerken</a></td></tr>
                @empty
                    <tr><td colspan="8" class="empty-state">Geen analyses gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px">{{ $assays->links() }}</div>
@endsection