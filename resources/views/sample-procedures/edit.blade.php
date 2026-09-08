@extends('layouts.app')
@section('title', 'MESA | Bemonsterprocedure bewerken')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Beheer / Analysestroom</div><h1>Bemonsterprocedure bewerken</h1></div><a class="button" href="{{ route('sample-procedures.index') }}">Terug naar overzicht</a></div>
    <form method="POST" action="{{ route('sample-procedures.update', $procedure) }}">@csrf @method('PUT')
        <fieldset class="form-section"><legend>Bemonsterprocedure</legend><div class="form-grid"><div class="field"><label for="name">Naam</label><input id="name" name="name" value="{{ old('name', $procedure->name) }}" maxlength="128" required autofocus></div><div class="field"><input type="hidden" name="hide" value="0"><label><input name="hide" type="checkbox" value="1" @checked(old('hide', $procedure->hide))> Verberg op rapport</label></div></div></fieldset>
        @if($fields->isNotEmpty())<fieldset class="form-section"><legend>Monstername velden</legend><div class="form-grid">@foreach($fields as $field)<div class="field"><label for="field_{{ $field->id }}">{{ $field->alias }}</label><input id="field_{{ $field->id }}" name="fields[{{ $field->id }}]" value="{{ old('fields.'.$field->id, $values[$field->name] ?? '') }}"></div>@endforeach</div></fieldset>@endif
        <div class="form-actions"><button class="button primary" type="submit">Opslaan</button><a class="button" href="{{ route('sample-procedures.index') }}">Annuleren</a></div>
    </form>
@endsection