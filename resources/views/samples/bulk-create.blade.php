@extends('layouts.app')
@section('title', 'MESA | '.($registrationType === 'legionella' ? 'Legionella' : 'RODAC').' aanmelden')
@section('content')
    <bulk-sample-create-form v-bind="{{ Illuminate\Support\Js::from([
        'registrationType' => $registrationType,
        'endpoints' => [
            'formData' => route('samples.bulk.form-data', ['registrationType' => $registrationType]),
            'clientSearch' => route('samples.clients.search'),
            'clientDetails' => route('samples.bulk.client-details', ['registrationType' => $registrationType, 'client' => '__CLIENT__']),
            'clientProjects' => route('samples.bulk.projects', ['registrationType' => $registrationType, 'client' => '__CLIENT__']),
            'project' => route('samples.projects.show', ['project' => '__PROJECT__']),
            'preview' => route('samples.bulk.preview', ['registrationType' => $registrationType]),
            'store' => route('samples.bulk.store', ['registrationType' => $registrationType]),
        ],
    ]) }}"></bulk-sample-create-form>
@endsection