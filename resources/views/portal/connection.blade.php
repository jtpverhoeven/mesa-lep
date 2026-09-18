@extends('layouts.app')

@section('title', 'Client portal verbinding')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">Beheer / Client Portal</div>
            <h1>Verbinding</h1>
        </div>
    </div>

    <portal-connection-form v-bind="{{ Illuminate\Support\Js::from([
        'settings' => $settings,
        'portalUrl' => $portalUrl,
        'updateUrl' => route('portal.connection.update'),
        'testUrl' => route('portal.connection.test'),
        'csrf' => csrf_token(),
    ]) }}"></portal-connection-form>
@endsection
