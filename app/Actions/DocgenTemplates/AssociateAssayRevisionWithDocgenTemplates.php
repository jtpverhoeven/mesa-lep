<?php

namespace App\Actions\DocgenTemplates;

use App\Models\Assay;

class AssociateAssayRevisionWithDocgenTemplates
{
    public function handle(Assay $previous, Assay $revision): void
    {
        $associations = $previous->docgenTemplates()->get()->mapWithKeys(
            fn ($template): array => [
                $template->id => ['original_assay_id' => $previous->original_id ?: $previous->id],
            ],
        );

        $revision->docgenTemplates()->syncWithoutDetaching($associations->all());
    }
}
