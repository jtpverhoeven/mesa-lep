<?php

namespace App\Actions\EmailTemplates;

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;

class BuildEmailTemplateSelector
{
    /**
     * @return array{
     *     options: list<array{id: int, name: ?string, selected: bool}>,
     *     template: ?string,
     *     bcc: ?string
     * }
     */
    public function handle(string $type, ?int $forcedId = null, string $language = 'nl'): array
    {
        $templateType = EmailTemplateType::from($type);
        $templates = EmailTemplate::query()->orderBy('id')->get();

        $selectedTemplate = $forcedId === null
            ? null
            : $templates->firstWhere('id', $forcedId);
        $selectedTemplate ??= $templates->first(
            fn (EmailTemplate $template): bool => (int) $template->getAttribute($templateType->defaultColumn()) === 1,
        );
        $selectedTemplate ??= $templates->first();

        return [
            'options' => $templates->map(fn (EmailTemplate $template): array => [
                'id' => (int) $template->id,
                'name' => $template->name,
                'selected' => $selectedTemplate?->is($template) ?? false,
            ])->all(),
            'template' => $selectedTemplate === null
                ? ''
                : ($language === 'nl' ? $selectedTemplate->template : $selectedTemplate->template_en),
            'bcc' => $selectedTemplate?->bcc ?? '',
        ];
    }
}
