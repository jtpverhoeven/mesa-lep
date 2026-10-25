@extends('layouts.app')
@section('title', 'MESA | Sjabloonassociaties')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Rapportage</div><h1>Analyse-associaties: {{ $template->name }}</h1></div>
        <a class="button" href="{{ route('docgen-templates.index') }}">Terug naar overzicht</a>
    </div>
    <docgen-association-form v-bind="{{ Illuminate\Support\Js::from([
        'assays' => $assays,
        'selectedAssayIds' => old('assay_ids', $selected),
        'templates' => $templates->map->only(['id', 'name'])->values(),
        'showHidden' => $showHidden,
        'formAction' => route('docgen-associations.update', $template),
        'copyAction' => route('docgen-associations.copy', $template),
        'toggleHiddenUrl' => route('docgen-associations.edit', [$template, 'show_hidden' => ! $showHidden]),
        'csrf' => csrf_token(),
        'sourceTemplate' => old('source_template', ''),
    ]) }}"></docgen-association-form>
@endsection
