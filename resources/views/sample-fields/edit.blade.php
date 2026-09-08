@extends('layouts.app')
@section('title', 'MESA | Monsterveld bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Monsterveld bewerken</h1></div><a class="button" href="{{ route('sample-fields.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('sample-fields.update', $field) }}">@csrf @method('PUT')
        @include('sample-fields.form', ['field' => $field])
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('sample-fields.index') }}">Annuleren</a></div>
    </form>
@endsection