@extends('layouts.app')
@section('title', 'MESA | Klantcategorieen')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Klanten</div><h1>Klantcategorieen</h1></div><a class="button" href="{{ route('clients.index') }}">Klantenoverzicht</a></div>
    <div data-client-category-manager="{{ json_encode(['categories' => $categories, 'storeUrl' => route('client-categories.store'), 'destroyUrl' => route('client-categories.destroy', ['clientCategory' => '__CATEGORY__']), 'csrf' => csrf_token(), 'canManage' => auth()->user()->can('clients.manage')]) }}"></div>
@endsection