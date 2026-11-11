@extends('layouts.app')
@section('title', 'MESA | Klant import')
@section('content')
    <sample-buffer-import v-bind="{{ Illuminate\Support\Js::from([
        'endpoints' => [
            'template' => route('sample-buffers.import.template'),
            'clientSearch' => route('sample-buffers.import.clients'),
            'preview' => route('sample-buffers.import.preview'),
            'store' => route('sample-buffers.import.store'),
        ],
    ]) }}"></sample-buffer-import>
@endsection
