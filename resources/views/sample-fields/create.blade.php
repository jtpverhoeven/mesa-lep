@extends('layouts.app')
@section('title', 'MESA | Monsterveld toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Monsterveld toevoegen</h1></div><a class="button" href="{{ route('sample-fields.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('sample-fields.store') }}">@csrf
        @include('sample-fields.form', ['field' => null])
        <div class="form-actions"><button class="button primary" type="submit">Monsterveld toevoegen</button><a class="button" href="{{ route('sample-fields.index') }}">Annuleren</a></div>
    </form>
@endsection