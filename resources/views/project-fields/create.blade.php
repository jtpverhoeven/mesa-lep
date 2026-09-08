@extends('layouts.app')
@section('title', 'MESA | Projectveld toevoegen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Projectveld toevoegen</h1></div><a class="button" href="{{ route('project-fields.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('project-fields.store') }}">@csrf
        @include('project-fields.form', ['field' => null])
        <div class="form-actions"><button class="button primary" type="submit">Projectveld toevoegen</button><a class="button" href="{{ route('project-fields.index') }}">Annuleren</a></div>
    </form>
@endsection