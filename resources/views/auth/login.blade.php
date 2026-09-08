@extends('layouts.app')

@section('title', 'MESA | Inloggen')

@section('content')
    <section class="login-panel">
        <div class="eyebrow">MESA LIMS</div>
        <h1>Inloggen</h1>
        @if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field"><label for="email">E-mailadres</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
            <div class="field"><label for="password">Wachtwoord</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
            <div class="check-grid"><label><input name="remember" type="checkbox" value="1" @checked(old('remember'))> Ingelogd blijven</label></div>
            <button type="submit" class="button primary">Inloggen</button>
        </form>
    </section>
@endsection