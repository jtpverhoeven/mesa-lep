<?php

namespace App\Actions\Reports;

use App\Actions\EmailTemplates\BuildEmailTemplateSelector;
use App\Actions\SampleFiles\ListProjectClientFiles;
use App\Models\Project;

class ShowFileSender
{
    public function __construct(
        private ListProjectClientFiles $files,
        private BuildEmailTemplateSelector $templates,
        private GetReportContactGroups $contactGroups,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Project $project): array
    {
        $template = $this->templates->handle('for_files');
        $groups = $this->contactGroups->handle((int) $project->getRawOriginal('client'), []);

        return [
            'project' => ['id' => $project->id, 'reference' => $project->reference, 'name' => $project->project_name],
            'files' => $this->files->rows($this->files->handle($project)),
            'contactGroups' => $groups['groups'],
            'contactGroupsError' => $groups['error'],
            'template' => $template,
            'bcc' => $template['bcc'],
            'urls' => [
                'project' => route('projects.search', ['project' => $project->id]),
                'reportForm' => route('projects.reports.index', $project),
                'mail' => route('projects.send-files.mail', $project),
                'template' => route('send.template', ['emailTemplate' => '__TEMPLATE__', 'lang' => '__LANG__']),
                'history' => route('sample-files.history', ['sampleFile' => '__FILE__']),
            ],
        ];
    }
}
