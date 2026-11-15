@extends('layouts.app')
@section('title', 'MESA | Facturatie')
@section('content')
    <billing-overview v-bind="{{ Illuminate\Support\Js::from([
        'endpoints' => [
            'overview' => route('billing.overview'),
            'export' => route('billing.export'),
            'clients' => route('billing.clients'),
            'markBilled' => route('billing.projects.mark-billed'),
            'setBilled' => route('billing.projects.set-billed'),
        ],
        'csrf' => csrf_token(),
    ]) }}"></billing-overview>
@endsection
