<?php

namespace App\Actions\SampleFiles;

use App\ChangeTracking\ChangeTracker;
use App\ClientPortal\ClientPortalService;
use App\ClientPortal\PortalSendOutcome;
use App\Models\Project;
use App\Models\SampleFile;
use Illuminate\Support\Collection;

class SendSampleFilesToPortal
{
    public function __construct(
        private ClientPortalService $portal,
        private RecordSampleFileDelivery $delivery,
        private ChangeTracker $changeTracker,
    ) {}

    /**
     * Sends files per sample through the portal document mailer; only delivered files are recorded.
     *
     * @param  Collection<int, SampleFile>  $files
     * @param  array{receivers: list<int|string>, extra: string, cc: string, bcc: string, message: string}  $mail
     * @return bool True when every sample was handed to the portal.
     */
    public function handle(Project $project, Collection $files, array $mail, ?string $groupNames): bool
    {
        $allSent = true;

        foreach ($files->groupBy('sample_id') as $sampleId => $sampleFiles) {
            $sent = PortalSendOutcome::handedOver(fn () => $this->portal->documentMailer(
                $mail['receivers'],
                $mail['extra'],
                $mail['cc'],
                $mail['bcc'],
                $mail['message'],
                $project->client,
                $sampleId,
                $project->id,
                $sampleFiles->pluck('hash_name')->values()->all(),
            ), 'mail_document');

            if (! $sent) {
                $allSent = false;

                continue;
            }

            foreach ($sampleFiles as $file) {
                $this->delivery->handle($file, 'filesender', null, ['groups' => $groupNames, 'extra' => $mail['extra']]);
            }

            $this->changeTracker->changed(21, $project->id, event: 'Document verstuurd naar portal-document-mailer');
        }

        return $allSent;
    }
}
