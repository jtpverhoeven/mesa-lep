<?php

namespace App\Actions\Reports;

use App\Actions\SampleFiles\ListProjectClientFiles;
use App\Actions\SampleFiles\RecordSampleFileDelivery;
use App\Actions\SampleFiles\SendSampleFilesToPortal;
use App\ChangeTracking\ChangeTracker;
use App\ClientPortal\ClientPortalService;
use App\ClientPortal\PortalSendOutcome;
use App\Models\Export;
use App\Models\SampleFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendReportTransaction
{
    public function __construct(
        private GetReportTransaction $transaction,
        private ResolveExportSampleScope $scope,
        private ListProjectClientFiles $files,
        private ResolveContactGroupNames $groupNames,
        private ClientPortalService $portal,
        private RecordSampleFileDelivery $delivery,
        private ChangeTracker $changeTracker,
        private SendSampleFilesToPortal $documentSender,
    ) {}

    /**
     * @param  array{receivers: list<int|string>, extra: string, cc: string, bcc: string, message: string, messageAtt: string, messageFiles: string, exportAll: bool, selectedReports: list<int>, includeSampleFiles: list<string>, includeOrphanedSampleFiles: list<string>}  $input
     * @return array{status: bool, message: string}
     *
     * @throws ReportSendRefusedException
     */
    public function handle(int $transaction, array $input): array
    {
        $exports = $this->transaction->handle($transaction);

        if ($exports->contains(fn (Export $export): bool => ! $export->was_authorized)) {
            throw ReportSendRefusedException::unauthorized();
        }

        $project = $exports->first()->projectRecord;
        $visibleFiles = $this->files->handle($project);
        $failures = '';
        $portalCallMade = false;

        foreach ($exports as $export) {
            if (! $input['exportAll'] && ! in_array($export->id, $input['selectedReports'], true)) {
                continue;
            }

            if (! $export->pdfExists()) {
                Log::warning('Report PDF missing while sending.', ['export_id' => $export->id]);

                continue;
            }

            $portalCallMade = true;

            if (! $this->sendReport($export, $input, $visibleFiles)) {
                $failures .= ';'.$export->projectRecord->reference;
            }
        }

        $orphanedFiles = $visibleFiles->whereIn('hash_name', $input['includeOrphanedSampleFiles']);

        if ($orphanedFiles->isNotEmpty()) {
            $portalCallMade = true;
            $mail = [...$this->mail($input), 'message' => $input['messageFiles']];

            $orphansSent = $this->documentSender->handle(
                $project,
                $orphanedFiles,
                $mail,
                $this->groupNames->handle($project->client, $input['receivers']),
            );

            if (! $orphansSent) {
                $failures .= ';'.$project->reference;
            }
        }

        if ($portalCallMade) {
            $this->notify($exports->pluck('hash')->all(), $project->client);
        }

        return ['status' => $failures === '', 'message' => $failures];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Collection<int, SampleFile>  $visibleFiles
     */
    private function sendReport(Export $export, array $input, $visibleFiles): bool
    {
        $project = $export->projectRecord;

        $contents = Storage::disk('local')->get($export->storagePath());
        $sampleIds = $this->scope->handle($export);
        $reportFiles = $this->files->handle($project, $sampleIds === [] ? null : $sampleIds);
        $sentFiles = $reportFiles->whereIn('hash_name', $input['includeSampleFiles'])->values();
        $hashes = $sentFiles->pluck('hash_name')->all();

        $sent = PortalSendOutcome::succeeded(fn () => $this->portal->reportMailer(
            $input['receivers'],
            $input['extra'],
            $input['cc'],
            $input['bcc'],
            $hashes !== [] ? $input['messageAtt'] : $input['message'],
            base64_encode($contents),
            md5($contents),
            $project->client,
            $export->hash,
            $export->project,
            $export->report_reference,
            $export->date,
            $export->revision,
            $export->print_version,
            $project->reference,
            $export->temporary,
            $export->reportName(true),
            $hashes,
        ), true, 'mail_report');

        if (! $sent) {
            return false;
        }

        $groupNames = $this->groupNames->handle($project->client, $input['receivers']);

        foreach ($sentFiles as $file) {
            $this->delivery->handle($file, 'report', $export->transaction, ['groups' => $groupNames, 'extra' => $input['extra']]);
        }

        $this->changeTracker->changed(21, $export->project, event: 'Project verstuurd naar portal-mailer. Contact-groepen: '.$groupNames
            .', Extra ontvanger: '.$input['extra']
            .' , CC: '.$input['cc']
            .' , BCC: '.$input['bcc']
            .'  ('.$export->report_reference.'.'.$export->revision.'.'.$export->print_version
            .'). Bestanden verstuurd mee-verstuurd: '.$sentFiles->pluck('original_name')->implode(','));

        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{receivers: list<int|string>, extra: string, cc: string, bcc: string, message: string}
     */
    private function mail(array $input): array
    {
        return [
            'receivers' => $input['receivers'],
            'extra' => $input['extra'],
            'cc' => $input['cc'],
            'bcc' => $input['bcc'],
            'message' => $input['message'],
        ];
    }

    /** @param  list<string>  $hashes */
    private function notify(array $hashes, int $clientId): void
    {
        try {
            $this->portal->reportMailerNotificationDispatch($hashes, $clientId);
        } catch (ConnectionException $exception) {
            report($exception);
        }
    }
}
