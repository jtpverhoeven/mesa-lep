<?php

namespace App\Actions\DocgenTemplates;

use App\Models\DocgenTemplate;

class SaveDocgenTemplate
{
    /** @param array{name: string, folder: string, active: bool|int|string, view_order: int|string|null} $data */
    public function handle(DocgenTemplate $template, array $data): DocgenTemplate
    {
        $template->fill($data)->save();

        return $template;
    }
}
