<?php

namespace App\Http\Controllers;

use App\Actions\EmailTemplates\DeleteEmailTemplate;
use App\Actions\EmailTemplates\ListEmailTemplates;
use App\Actions\EmailTemplates\LoadEmailTemplate;
use App\Actions\EmailTemplates\SaveEmailTemplate;
use App\Actions\EmailTemplates\SetDefaultEmailTemplate;
use App\Enums\EmailTemplateType;
use App\Http\Requests\SaveEmailTemplateRequest;
use App\Http\Requests\SetDefaultEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(ListEmailTemplates $listEmailTemplates): View
    {
        return view('email-templates.index', [
            'emailTemplates' => $listEmailTemplates->handle(),
            'types' => EmailTemplateType::directoryTypes(),
        ]);
    }

    public function edit(?int $emailTemplate = null): View|RedirectResponse
    {
        $template = $emailTemplate === null
            ? new EmailTemplate
            : EmailTemplate::query()->find($emailTemplate);

        if ($template === null) {
            return to_route('email-templates.index');
        }

        return view('email-templates.edit', [
            'emailTemplate' => $template,
            'defaults' => collect(EmailTemplateType::directoryTypes())
                ->filter(fn (array $type): bool => (int) $template->getAttribute($type['column']) === 1)
                ->pluck('label')
                ->values(),
        ]);
    }

    public function store(SaveEmailTemplateRequest $request, SaveEmailTemplate $saveEmailTemplate): RedirectResponse
    {
        $saveEmailTemplate->handle($request->validated());

        return to_route('email-templates.index')->with('success', 'Email template aangemaakt.');
    }

    public function update(
        SaveEmailTemplateRequest $request,
        EmailTemplate $emailTemplate,
        SaveEmailTemplate $saveEmailTemplate,
    ): RedirectResponse {
        $saveEmailTemplate->handle($request->validated(), $emailTemplate);

        return to_route('email-templates.index')->with('success', 'Email template bijgewerkt.');
    }

    public function destroy(EmailTemplate $emailTemplate, DeleteEmailTemplate $deleteEmailTemplate): RedirectResponse
    {
        $deleteEmailTemplate->handle($emailTemplate);

        return to_route('email-templates.index')->with('success', 'Email template verwijderd.');
    }

    public function setDefault(
        SetDefaultEmailTemplateRequest $request,
        EmailTemplate $emailTemplate,
        SetDefaultEmailTemplate $setDefaultEmailTemplate,
    ): RedirectResponse {
        $type = EmailTemplateType::from($request->validated('defaultFor'));
        $setDefaultEmailTemplate->handle($emailTemplate, $type);

        return to_route('email-templates.index')->with('success', 'Standaard email template bijgewerkt.');
    }

    public function loadTemplate(
        EmailTemplate $emailTemplate,
        LoadEmailTemplate $loadEmailTemplate,
        string $lang = 'nl',
    ): JsonResponse {
        return response()->json($loadEmailTemplate->handle($emailTemplate, $lang));
    }
}
