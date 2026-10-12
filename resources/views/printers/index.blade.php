@extends('layouts.app')
@section('title', 'MESA | Printers')
@section('content')
    <printer-directory v-bind="{{ Illuminate\Support\Js::from([
        'printers' => $printers,
        'types' => $types,
        'createUrl' => route('printers.create'),
        'editUrl' => route('printers.edit', '__PRINTER__'),
        'destroyUrl' => route('printers.destroy', '__PRINTER__'),
        'csrf' => csrf_token(),
    ]) }}"></printer-directory>
@endsection