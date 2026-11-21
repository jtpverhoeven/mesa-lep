<?php

namespace App\Support;

use App\Models\User;

class AuditTrailLinks
{
    /**
     * URL templates for the places an audit trail refers to; a link the user may not follow is left empty.
     *
     * @return array{client: string, project: string, lookup: string, assuranceForm: string}
     */
    public static function for(User $user): array
    {
        return [
            'client' => $user->can('clients.manage') ? route('clients.edit', ['client' => '__CLIENT__']) : '',
            'project' => $user->can('projects.view') ? route('projects.search', ['project' => '__PROJECT__']) : '',
            'lookup' => $user->can('samples.view') ? route('samples.lookup', ['barcode' => '__BARCODE__']) : '',
            'assuranceForm' => $user->can('assurance-form.view') ? route('assurance-forms.show', ['date' => '__DATE__']) : '',
        ];
    }
}
