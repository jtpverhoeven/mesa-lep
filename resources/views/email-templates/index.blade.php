@extends('layouts.app')
@section('title', 'MESA | Uitgaande email')
@section('content')
    <email-template-directory v-bind="{{ Illuminate\Support\Js::from([
        'templates' => $emailTemplates->map(fn ($emailTemplate): array => [
            'id' => $emailTemplate->id,
            'name' => $emailTemplate->name,
            'bcc' => $emailTemplate->bcc,
            'hasTemplate' => filled($emailTemplate->template),
            'hasTemplateEn' => filled($emailTemplate->template_en),
            'defaultmail' => (int) $emailTemplate->defaultmail === 1,
            'defaultmail_with_files' => (int) $emailTemplate->defaultmail_with_files === 1,
            'defaultmail_for_files' => (int) $emailTemplate->defaultmail_for_files === 1,
        ])->values(),
        'types' => $types,
        'createUrl' => route('email-templates.edit'),
        'editUrl' => route('email-templates.edit', '__EMAIL_TEMPLATE__'),
        'destroyUrl' => route('email-templates.destroy', '__EMAIL_TEMPLATE__'),
        'defaultUrl' => route('email-templates.default', '__EMAIL_TEMPLATE__'),
        'csrf' => csrf_token(),
    ]) }}"></email-template-directory>
@endsection
