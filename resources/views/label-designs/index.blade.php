@extends('layouts.app')
@section('title', 'MESA | Labelontwerpen')
@section('content')
    <label-design-directory v-bind="{{ Illuminate\Support\Js::from([
        'labelDesigns' => $labelDesigns,
        'createUrl' => route('label-designs.create'),
        'editUrl' => route('label-designs.edit', '__LABEL_DESIGN__'),
        'destroyUrl' => route('label-designs.destroy', '__LABEL_DESIGN__'),
        'csrf' => csrf_token(),
    ]) }}"></label-design-directory>
@endsection