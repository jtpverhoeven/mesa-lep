@extends('layouts.app')
@section('title', 'MESA | Labelregel toevoegen')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Labels</div><h1>Labelregel toevoegen</h1></div>
        <a class="button" href="{{ route('label-events.index', ['group' => $labelEvent->event_group]) }}">Terug naar overzicht</a>
    </div>
    @include('label-events._form')
@endsection