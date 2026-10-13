@extends('layouts.app')
@section('title', 'MESA | Printprofielen')
@section('content')
    <print-profile-directory v-bind="{{ Illuminate\Support\Js::from([
        'profiles' => $printProfiles,
        'createUrl' => route('print-profiles.create'),
        'editUrl' => route('print-profiles.edit', '__PRINT_PROFILE__'),
        'destroyUrl' => route('print-profiles.destroy', '__PRINT_PROFILE__'),
        'csrf' => csrf_token(),
    ]) }}"></print-profile-directory>
@endsection