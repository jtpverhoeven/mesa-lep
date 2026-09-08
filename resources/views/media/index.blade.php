@extends('layouts.app')
@section('title', 'MESA | Media en materiaal')
@php
    $typeLabels = [1 => 'Telling', 2 => 'Aanwezigheid', 3 => 'Materiaal'];
@endphp
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Analytisch</div><h1>Media en materiaal</h1></div>
        <div class="heading-actions">
            <a class="button" href="{{ route('media.create', ['type' => 3]) }}">Materiaal toevoegen</a>
            <a class="button primary" href="{{ route('media.create') }}">Media toevoegen</a>
        </div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">ID</th><th scope="col">Naam</th><th scope="col">Korte naam</th><th scope="col">Bevestigingsmedia</th><th scope="col">Soort</th><th scope="col">Supplementen / eenheid</th><th scope="col">Heeft datum / positie</th><th scope="col">Acties</th></tr></thead>
            <tbody>
                @forelse($media as $medium)
                    @php
                        $supplements = json_decode((string) $medium->supplements, true);
                        $supplementNames = is_array($supplements)
                            ? collect($supplements)->map(fn ($supplement) => is_array($supplement) ? ($supplement['name'] ?? '') : $supplement)->filter()->implode(', ')
                            : (string) $medium->supplements;
                    @endphp
                    <tr>
                        <td>{{ $medium->id }}</td>
                        <td><strong>{{ $medium->name }}</strong></td>
                        <td>{{ $medium->short_name ?: '-' }}</td>
                        <td>{{ $medium->confirmation_media ? 'Ja' : 'Nee' }}</td>
                        <td>{{ $typeLabels[$medium->type] ?? $medium->type }}</td>
                        <td style="white-space:normal">{{ $supplementNames ?: '-' }}</td>
                        <td>{{ $medium->hasDate ? 'Ja' : 'Nee' }}</td>
                        <td>
                            <a class="text-link" href="{{ route('media.edit', $medium) }}">Bewerken</a>
                            <form class="inline-form" method="POST" action="{{ route('media.destroy', $medium) }}" onsubmit="return confirm('Dit item verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-button" type="submit">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">Geen media of materiaal gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection