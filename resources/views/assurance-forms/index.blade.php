@extends('layouts.app')
@section('title', 'MESA | Borgingsformulier')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Laboratorium / Borging</div><h1>Borgingsformulier</h1></div>
    </div>
    <assurance-form-viewer v-bind="{{ Illuminate\Support\Js::from([
        'formDate' => $formDate,
        'incompleteForms' => $incompleteForms,
        'endpoints' => [
            'page' => route('assurance-forms.show', ['date' => '__DATE__']),
            'show' => route('assurance-forms.show', ['date' => '__DATE__']),
            'store' => route('assurance-forms.store', ['date' => '__DATE__']),
            'updateField' => route('assurance-forms.fields.update', ['assuranceForm' => '__FORM__']),
            'updateExplanation' => route('assurance-forms.explanation.update', ['assuranceForm' => '__FORM__']),
            'print' => route('assurance-forms.print', ['assuranceForm' => '__FORM__']),
        ],
    ]) }}"></assurance-form-viewer>
@endsection
