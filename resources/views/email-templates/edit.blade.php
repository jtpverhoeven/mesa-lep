@extends('layouts.app')
@section('title', 'MESA | Email template '.($emailTemplate->exists ? 'bewerken' : 'toevoegen'))
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Beheer / Client Portal</div><h1>Email template {{ $emailTemplate->exists ? 'bewerken' : 'toevoegen' }}</h1></div>
        <a class="button" href="{{ route('email-templates.index') }}">Terug naar overzicht</a>
    </div>
    <email-template-form v-bind="{{ Illuminate\Support\Js::from([
        'initial' => [
            'name' => old('name', $emailTemplate->name),
            'template' => old('template', $emailTemplate->template),
            'template_en' => old('template_en', $emailTemplate->template_en),
            'bcc' => old('bcc', $emailTemplate->bcc),
        ],
        'defaults' => $defaults,
        'errors' => (object) $errors->getBag('default')->messages(),
        'actionUrl' => $emailTemplate->exists ? route('email-templates.update', $emailTemplate) : route('email-templates.store'),
        'cancelUrl' => route('email-templates.index'),
        'csrf' => csrf_token(),
        'updating' => $emailTemplate->exists,
    ]) }}"></email-template-form>
@endsection
