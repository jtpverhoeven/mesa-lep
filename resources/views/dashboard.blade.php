@extends('layouts.app')

@section('title', 'Mesa | Dashboard')

@section('content')
	<div class="page-heading"><div><div class="eyebrow">LIMS / Overzicht</div><h1>Dashboard</h1></div></div>
	<p class="muted">{{ auth()->user()->name }}</p>
	@can('viewAny', \App\Models\Assay::class)
		<div class="form-actions" style="margin-top:24px"><a class="button primary" href="{{ route('assays.index') }}">Analyses beheren</a><a class="button" href="{{ route('assays.create') }}">Analyse toevoegen</a></div>
	@endcan
@endsection