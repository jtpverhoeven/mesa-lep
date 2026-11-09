<?php

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Collection;

class ListEmailTemplates
{
    /** @return Collection<int, EmailTemplate> */
    public function handle(): Collection
    {
        return EmailTemplate::query()->orderBy('id')->get();
    }
}
