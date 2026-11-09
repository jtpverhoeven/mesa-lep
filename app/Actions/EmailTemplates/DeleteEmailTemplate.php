<?php

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;

class DeleteEmailTemplate
{
    public function handle(EmailTemplate $emailTemplate): void
    {
        $emailTemplate->delete();
    }
}
