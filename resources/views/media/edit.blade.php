@extends('layouts.app')
@section('title', 'MESA | Media bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Media en materiaal</div><h1>{{ (int) $media->type === 3 ? 'Materiaal' : 'Media' }} bewerken</h1></div><a class="button" href="{{ route('media.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('media.update', $media) }}">
        @csrf
        @method('PUT')
        @include('media._form', ['type' => $media->type])
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('media.index') }}">Annuleren</a></div>
    </form>
@endsection