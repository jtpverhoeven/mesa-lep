@extends('layouts.app')
@section('title', 'MESA | Klantcategorieen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Klanten</div><h1>Klantcategorieen</h1></div><div class="heading-actions">@if(auth()->user()->can('clients.manage'))<a class="button primary" href="{{ route('client-categories.create') }}">Klantcategorie toevoegen</a>@endif<a class="button" href="{{ route('clients.index') }}">Klantenoverzicht</a></div></div>
    <client-category-manager v-bind="{{ Illuminate\Support\Js::from(['categories' => $categories, 'destroyUrl' => route('client-categories.destroy', ['clientCategory' => '__CATEGORY__']), 'csrf' => csrf_token(), 'canManage' => auth()->user()->can('clients.manage')]) }}"></client-category-manager>
@endsection