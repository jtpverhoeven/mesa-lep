@extends('layouts.app')
@section('title', 'MESA | Projectveld bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Projectveld bewerken</h1></div><a class="button" href="{{ route('project-fields.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('project-fields.update', $field) }}">@csrf @method('PUT')
        @include('project-fields.form', ['field' => $field])
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('project-fields.index') }}">Annuleren</a></div>
    </form>
@endsection