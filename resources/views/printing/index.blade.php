@extends('layouts.app')
@section('title', 'MESA | Labels printen')
@section('content')
    <manual-label-printing v-bind="{{ Illuminate\Support\Js::from([
        'options' => $options,
        'printUrl' => route('printing.store'),
        'csrf' => csrf_token(),
    ]) }}"></manual-label-printing>
@endsection