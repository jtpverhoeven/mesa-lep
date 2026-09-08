@extends('layouts.app')

@section('title', 'mesaLIMS')

@section('content')
	<section class="mx-auto max-w-2xl py-16 text-center">
		<p class="text-sm font-medium uppercase tracking-[0.2em] text-stone-500">Mesa</p>
		


		<div class="mt-8">
			@auth
				<a href="{{ route('dashboard') }}" class="inline-flex rounded-md bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">Go to dashboard</a>
			@else
				<a href="{{ route('login') }}" class="inline-flex rounded-md bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">Sign in</a>
			@endauth
		</div>
	</section>
@endsection