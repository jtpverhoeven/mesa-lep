<?php

namespace App\Actions\EmailTemplates;

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SetDefaultEmailTemplate
{
    public function handle(EmailTemplate $emailTemplate, EmailTemplateType $type): EmailTemplate
    {
        $column = $type->defaultColumn();

        return DB::transaction(function () use ($emailTemplate, $column): EmailTemplate {
            $templates = EmailTemplate::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $selectedTemplate = $templates->firstWhere('id', $emailTemplate->id);

            if ($selectedTemplate === null) {
                throw (new ModelNotFoundException)->setModel(EmailTemplate::class, [$emailTemplate->id]);
            }

            EmailTemplate::query()->update([$column => 0]);
            EmailTemplate::query()->whereKey($selectedTemplate->id)->update([$column => 1]);

            return $selectedTemplate->refresh();
        }, 3);
    }
}
