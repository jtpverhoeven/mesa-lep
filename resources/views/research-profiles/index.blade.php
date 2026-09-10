@extends('layouts.app')
@section('title', 'MESA | Onderzoeksprofielen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysestroom</div><h1>Onderzoeksprofielen</h1></div></div>
    <research-profile-directory v-bind="{{ Illuminate\Support\Js::from(['endpoints' => [
        'list' => route('research-profiles.list'),
        'create' => route('research-profiles.create'),
        'bulkPage' => route('research-profiles.bulk'),
        'edit' => url('/admin/research-profiles/__ID__/edit'),
        'remove' => url('/admin/research-profiles/__ID__'),
    ]]) }}"></research-profile-directory>
@endsection