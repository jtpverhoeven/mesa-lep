<?php

namespace App\Actions\DocgenTemplates;

use App\Models\DocgenTemplate;
use Illuminate\Support\Facades\DB;

class DeleteDocgenTemplate
{
    public function handle(DocgenTemplate $template): void
    {
        DB::transaction(function () use ($template): void {
            $template->associations()->delete();
            $template->delete();
        });
    }
}
