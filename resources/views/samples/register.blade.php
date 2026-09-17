@extends('layouts.app')
@section('title', 'MESA | Monsterlijst')
@section('content')
    {{-- <div class="page-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>Monsterlijst</h1></div></div> --}}
    <sample-register v-bind="{{ Illuminate\Support\Js::from(['endpoints' => ['data' => route('samples.register.data'), 'inoculate' => route('samples.register.inoculation'), 'settings' => route('samples.register.settings'), 'conditions' => route('samples.register.conditions', ['sample' => '__SAMPLE__']), 'lookup' => route('samples.lookup', ['barcode' => '__BARCODE__'])]]) }}"></sample-register>
@endsection