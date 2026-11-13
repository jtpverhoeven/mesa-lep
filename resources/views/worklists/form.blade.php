@extends('layouts.app')
@section('title', 'MESA | Werklijst '.($worklist->exists ? 'bewerken' : 'toevoegen'))
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Werklijst {{ $worklist->exists ? 'bewerken' : 'toevoegen' }}</h1></div>
        <a class="button" href="{{ route('worklists.index') }}">Terug naar overzicht</a>
    </div>
    <worklist-form v-bind="{{ Illuminate\Support\Js::from([
        'initial' => $initial,
        'assays' => $assays,
        'errors' => (object) $errors->getBag('default')->messages(),
        'actionUrl' => $worklist->exists ? route('worklists.update', $worklist) : route('worklists.store'),
        'cancelUrl' => route('worklists.index'),
        'csrf' => csrf_token(),
        'updating' => $worklist->exists,
    ]) }}"></worklist-form>
@endsection
