@extends('layouts.app')

@section('title', 'Client portal productgroepen')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">Beheer / Client Portal</div>
            <h1>Productgroepen</h1>
        </div>
    </div>

    <product-group-directory v-bind="{{ Illuminate\Support\Js::from(['dataUrl' => route('portal.product-groups.data')]) }}"></product-group-directory>
@endsection