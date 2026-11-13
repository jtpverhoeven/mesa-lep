@extends('layouts.app')
@section('title', 'MESA | Bevestigingstabel '.($table->exists ? 'bewerken' : 'toevoegen'))
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Media &amp; bevestigingen</div><h1>Bevestigingstabel {{ $table->exists ? 'bewerken' : 'toevoegen' }}</h1></div>
        <a class="button" href="{{ route('confirmation-tables.index') }}">Terug naar overzicht</a>
    </div>
    <confirmation-table-editor v-bind="{{ Illuminate\Support\Js::from([
        'initial' => ['name' => old('name', $table->name), 'html' => old('html', $table->html)],
        'errors' => (object) $errors->getBag('default')->messages(),
        'actionUrl' => $table->exists ? route('confirmation-tables.update', $table) : route('confirmation-tables.store'),
        'cancelUrl' => route('confirmation-tables.index'),
        'csrf' => csrf_token(),
        'updating' => $table->exists,
    ]) }}"></confirmation-table-editor>
@endsection
