@extends('layouts.app')

@section('title', 'Mesa | Dashboard')

@section('content')
	<div class="page-heading"><div><div class="eyebrow">LIMS / Overzicht</div><h1>Dashboard</h1></div></div>
	<p class="muted">{{ auth()->user()->name }}</p>
	
@endsection