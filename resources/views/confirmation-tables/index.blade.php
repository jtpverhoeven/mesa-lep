@extends('layouts.app')
@section('title', 'MESA | Bevestigingstabellen')
@section('content')
    <confirmation-table-directory v-bind="{{ Illuminate\Support\Js::from([
        'tables' => $tables->map(fn ($table): array => ['id' => $table->id, 'name' => $table->name, 'assays_count' => $table->assays_count])->values(),
        'createUrl' => route('confirmation-tables.create'),
        'editUrl' => route('confirmation-tables.edit', '__TABLE__'),
        'destroyUrl' => route('confirmation-tables.destroy', '__TABLE__'),
        'csrf' => csrf_token(),
    ]) }}"></confirmation-table-directory>
@endsection
