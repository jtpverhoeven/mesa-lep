<?php

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;

class LoadEmailTemplate
{
    /** @return array{template: ?string, bcc: ?string} */
    public function handle(EmailTemplate $emailTemplate, string $language = 'nl'): array
    {
        return [
            'template' => $language === 'nl' ? $emailTemplate->template : $emailTemplate->template_en,
            'bcc' => $emailTemplate->bcc,
        ];
    }
}
