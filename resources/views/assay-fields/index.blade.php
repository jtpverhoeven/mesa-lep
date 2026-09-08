@extends('layouts.app')
@section('title', 'MESA | Analysevelden')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Analyses</div><h1>Analysevelden</h1></div>
        <div class="heading-actions">
            <a class="button primary" href="{{ route('assay-fields.create') }}">Analyseveld toevoegen</a>
        </div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">ID</th><th scope="col">Veldnaam</th><th scope="col">Standaardwaarde</th><th scope="col">Positie</th><th scope="col">Acties</th></tr></thead>
            <tbody>
                @forelse($fields as $field)
                    <tr>
                        <td>{{ $field->id }}</td>
                        <td><strong>{{ $field->name }}</strong></td>
                        <td>{{ $field->standard_value ?? '-' }}</td>
                        <td>{{ $field->position }}</td>
                        <td>
                            <a class="text-link" href="{{ route('assay-fields.edit', $field) }}">Bewerken</a>
                            <form class="inline-form" method="POST" action="{{ route('assay-fields.destroy', $field) }}" onsubmit="return confirm('Dit analyseveld verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-button" type="submit">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Geen analysevelden gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection