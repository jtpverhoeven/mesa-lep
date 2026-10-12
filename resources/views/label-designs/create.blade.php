@extends('layouts.app')
@section('title', 'MESA | Labelontwerp toevoegen')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Labels</div><h1>Labelontwerp toevoegen</h1></div>
        <a class="button" href="{{ route('label-designs.index') }}">Terug naar overzicht</a>
    </div>
    <form class="label-design-form" method="POST" action="{{ route('label-designs.store') }}">
        @csrf
        @include('label-designs._form')
    </form>
@endsection