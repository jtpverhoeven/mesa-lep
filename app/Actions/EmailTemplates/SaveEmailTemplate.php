<?php

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\DB;

class SaveEmailTemplate
{
    /**
     * @param  array{name: string, template?: ?string, template_en?: ?string, bcc?: ?string}  $data
     */
    public function handle(array $data, ?EmailTemplate $emailTemplate = null): EmailTemplate
    {
        return DB::transaction(function () use ($data, $emailTemplate): EmailTemplate {
            $attributes = [
                'name' => $data['name'],
                'template' => $data['template'] ?? null,
                'template_en' => $data['template_en'] ?? null,
                'bcc' => $data['bcc'] ?? null,
            ];

            if ($emailTemplate === null) {
                return EmailTemplate::query()->create($attributes);
            }

            $emailTemplate->update($attributes);

            return $emailTemplate->refresh();
        });
    }
}
