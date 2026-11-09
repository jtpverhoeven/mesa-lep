<?php

namespace App\Actions\SampleFiles;

use App\Actions\Reports\ResolveContactGroupNames;
use App\Models\Project;

class SendProjectFiles
{
    public function __construct(
        private ListProjectClientFiles $files,
        private ResolveContactGroupNames $groupNames,
        private SendSampleFilesToPortal $sender,
    ) {}

    /**
     * @param  array{receivers: list<int|string>, extra: string, cc: string, bcc: string, message: string}  $mail
     * @param  list<string>  $hashes
     * @return array{status: bool, message: string}
     */
    public function handle(Project $project, array $hashes, array $mail): array
    {
        $files = $this->files->handle($project)->whereIn('hash_name', $hashes);

        if ($files->isEmpty()) {
            return ['message' => 'empty'];
        }

        $sent = $this->sender->handle($project, $files, $mail, $this->groupNames->handle($project->client, $mail['receivers']));

        return $sent
            ? ['message' => 'ok']
            : ['status' => false, 'message' => 'De bestanden konden niet aan de portal worden afgedragen.'];
    }
}
