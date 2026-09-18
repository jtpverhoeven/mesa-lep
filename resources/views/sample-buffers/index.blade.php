@extends('layouts.app')
@section('title', $initialTab === 'staged_tht' ? 'MESA | THT onderzoeken' : 'MESA | Voorportaal')
@section('content')
    <sample-buffer-portal v-bind="{{ Illuminate\Support\Js::from([
        'initialTab' => $initialTab,
        'endpoints' => [
            'data' => route('sample-buffers.data'),
            'batch' => route('sample-buffers.batch'),
        ],
    ]) }}"></sample-buffer-portal>
@endsection