@extends('layouts.app')

@section('title', 'MESA | Inloggen')

@section('content')
    <login-form v-bind="{{ Illuminate\Support\Js::from([
        'action' => route('login'),
        'csrf' => csrf_token(),
        'initialLogin' => old('login', ''),
        'remember' => (bool) old('remember'),
        'status' => session('status', ''),
    ]) }}"></login-form>
@endsection