@extends('layouts.app')
@section('title', 'MESA | Printprofiel '.($printProfile->exists ? 'bewerken' : 'aanmaken'))
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Labels</div><h1>Printprofiel {{ $printProfile->exists ? 'bewerken' : 'aanmaken' }}</h1></div>
        <a class="button" href="{{ route('print-profiles.index') }}">Terug naar overzicht</a>
    </div>
    <print-profile-form v-bind="{{ Illuminate\Support\Js::from([
        'initial' => ['name' => old('name', $printProfile->name), 'profile' => old('profile', $printProfile->profile)],
        'options' => $options,
        'errors' => (object) $errors->getBag('default')->messages(),
        'actionUrl' => $printProfile->exists ? route('print-profiles.update', $printProfile) : route('print-profiles.store'),
        'testUrl' => route('print-profiles.test'),
        'cancelUrl' => route('print-profiles.index'),
        'csrf' => csrf_token(),
        'updating' => $printProfile->exists,
    ]) }}"></print-profile-form>
@endsection