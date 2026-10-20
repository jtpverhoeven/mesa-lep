@extends('layouts.app')
@section('title', 'Mesa | Accountinstellingen')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Mijn account</div><h1>Accountinstellingen</h1></div>
    </div>
    <account-settings v-bind="{{ Illuminate\Support\Js::from([
        'account' => $account,
        'profileUrl' => route('account.profile.update'),
        'aliasUrl' => route('account.alias.update'),
        'passwordUrl' => route('account.password.update'),
        'imageUrls' => collect(['avatar', 'nl', 'en'])->mapWithKeys(fn ($kind) => [$kind => route('account.images.store', $kind)])->all(),
        'csrf' => csrf_token(),
    ]) }}"></account-settings>
@endsection
