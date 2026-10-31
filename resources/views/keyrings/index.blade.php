@extends('layouts.app')

@section('title', 'Vergrendelingen')

@section('content')
    <keyring-administration v-bind="{{ Illuminate\Support\Js::from([
        'dataUrl' => $dataUrl,
        'destroyUrl' => $destroyUrl,
        'csrf' => $csrf,
    ]) }}"></keyring-administration>
@endsection
