@extends('layouts.app')
@section('title', 'MESA | Printer bewerken')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Labels</div><h1>Printer bewerken</h1></div>
        <a class="button" href="{{ route('printers.index') }}">Terug naar overzicht</a>
    </div>
    <form method="POST" action="{{ route('printers.update', $printer) }}">
        @csrf
        @method('PUT')
        @include('printers._form')
    </form>
@endsection