<?php

namespace App\Support;

use App\Models\User;

class NoteClientConfig
{
    /**
     * @return array{endpoints: array<string, string>, permissions: array<string, bool>}
     */
    public static function for(User $user): array
    {
        return [
            'endpoints' => [
                'users' => route('notes.users'),
                'templates' => route('note-templates.index'),
                'template' => route('note-templates.destroy', ['noteTemplate' => '__TEMPLATE__']),
                'sampleNotes' => route('samples.notes.store', ['sample' => '__SAMPLE__']),
                'sampleNote' => route('samples.notes.destroy', ['sample' => '__SAMPLE__', 'note' => '__NOTE__']),
                'projectReportNote' => route('projects.report-note.update', ['project' => '__PROJECT__']),
                'sampleReportNote' => route('projects.samples.report-note.update', ['project' => '__PROJECT__', 'sample' => '__SAMPLE__']),
            ],
            'permissions' => [
                'addSampleNotes' => $user->can('samples.notes.update'),
                'editReportNotes' => $user->can('projects.notes.create'),
            ],
        ];
    }
}
