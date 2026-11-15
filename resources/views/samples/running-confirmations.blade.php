@extends('layouts.app')
@section('title', 'MESA | Lopende bevestigingen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>Lopende bevestigingen</h1></div></div>
    <running-confirmations v-bind="{{ Illuminate\Support\Js::from([
        'confirmations' => $confirmations,
        'lookupUrl' => route('samples.lookup', ['barcode' => '__BARCODE__']),
        'projectUrl' => auth()->user()->can('projects.view') ? route('projects.search', ['project' => '__PROJECT__']) : '',
        'clientUrl' => auth()->user()->can('clients.manage') ? route('clients.edit', ['client' => '__CLIENT__']) : '',
    ]) }}"></running-confirmations>
@endsection
