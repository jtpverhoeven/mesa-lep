@extends('layouts.app')

@section('title', 'Geavanceerde instellingen')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">Beheer / Systeembeheer</div>
            <h1>Geavanceerde instellingen</h1>
        </div>
    </div>

    <cvar-table v-bind="{{ Illuminate\Support\Js::from([
        'cvars' => $cvars,
        'updateUrl' => route('cvars.update', ['cvar' => '__CVAR__']),
        'csrf' => csrf_token(),
    ]) }}"></cvar-table>
@endsection