@extends('layouts.app')
@section('title', 'MESA | Media toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Media en materiaal</div><h1>Media toevoegen</h1></div><a class="button" href="{{ route('media.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('media.store') }}">
        @csrf
        @include('media._form', ['media' => null])
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('media.index') }}">Annuleren</a></div>
    </form>
@endsection