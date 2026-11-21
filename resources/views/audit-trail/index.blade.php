@extends('layouts.app')
@section('title', 'MESA | Audit trail')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Laboratorium / Borging</div><h1>Audit trail</h1></div></div>
    <audit-trail v-bind="{{ Illuminate\Support\Js::from([
        'initialBarcode' => $initialBarcode,
        'endpoints' => [
            'page' => route('audit-trail'),
            'data' => route('audit-trail.data'),
            'print' => route('audit-trail.print', ['barcode' => '__BARCODE__']),
        ],
        'links' => App\Support\AuditTrailLinks::for(auth()->user()),
    ]) }}"></audit-trail>
@endsection
