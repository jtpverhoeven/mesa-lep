@extends('layouts.app')
@section('title', 'MESA | Projecten - '.$title)
@section('content')
    <project-overview v-bind="{{ Illuminate\Support\Js::from([
        'status' => $status,
        'title' => $title,
        'endpoint' => route('projects.overview.'.$status.'.data'),
    ]) }}"></project-overview>
@endsection