<?php

namespace App\ClientPortal;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class ClientPortalService
{
    public function __construct(
        private readonly Factory $http,
        private readonly PortalSettings $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public function connectionTest(): Response
    {
        return $this->get('connection_test', 'connectionTest');
    }

    public function dropTht(mixed $portalSampleId): Response
    {
        return $this->post('drop_tht', 'drop-tht-from-sample', [
            'sample_id' => $portalSampleId,
        ]);
    }

    public function changeToTht(mixed $portalSampleId, mixed $date, mixed $storage): Response
    {
        return $this->post('change_to_tht', 'changeSampleToTHT', [
            'sample_id' => $portalSampleId,
            'tht_start_date' => $date,
            'tht_storage' => $storage,
        ]);
    }

    public function acceptSamples(array $samples): Response
    {
        return $this->post('accept_samples', 'acceptSamples', [
            'samples' => json_encode($samples, JSON_THROW_ON_ERROR),
        ]);
    }

    public function projectAuthorized(mixed $projectId, mixed $quiet): Response
    {
        return $this->post('project_authorized', 'projectAuthorized/'.$projectId, [
            'quiet' => $quiet,
        ]);
    }

    public function projectDeauthorized(mixed $projectId, bool $previewFlag): Response
    {
        return $this->post('project_deauthorized', 'projectDeauthorized', [
            'project_id' => $projectId,
            'preview' => $previewFlag ? 'True' : 'False',
        ]);
    }

    public function sampleStart(mixed $portalSampleId, mixed $inoculationDate, mixed $estimatedEnd): Response
    {
        return $this->post('sample_start', 'sampleInnoc', [
            'sample_id' => $portalSampleId,
            'innoc_time' => $inoculationDate,
            'estimated_end' => $estimatedEnd,
        ]);
    }

    public function destroySample(mixed $portalSampleIds): Response
    {
        $portalSampleIds = is_array($portalSampleIds) ? $portalSampleIds : [$portalSampleIds];

        return $this->post('destroy_sample', 'destroySample', [
            'sample_ids' => json_encode($portalSampleIds, JSON_THROW_ON_ERROR),
        ]);
    }

    public function setThtFlag(mixed $portalSampleIds): Response
    {
        $portalSampleIds = is_array($portalSampleIds) ? $portalSampleIds : [$portalSampleIds];

        return $this->post('set_tht_flag', 'accept-tht', [
            'sample_ids' => json_encode($portalSampleIds, JSON_THROW_ON_ERROR),
        ]);
    }

    public function documentMailer(
        mixed $receivers,
        mixed $extraMail,
        mixed $cc,
        mixed $bcc,
        mixed $message,
        mixed $client,
        mixed $sample,
        mixed $project,
        mixed $hashes,
    ): Response {
        return $this->post('mail_document', 'mail-document', [
            'emails' => json_encode($receivers, JSON_THROW_ON_ERROR),
            'extraMail' => $extraMail,
            'cc' => $cc,
            'bcc' => $bcc,
            'message' => $message,
            'client' => $client,
            'sample' => $sample,
            'project' => $project,
            'sampleFileHashes' => json_encode($hashes, JSON_THROW_ON_ERROR),
        ]);
    }

    public function reportMailer(
        mixed $receivers,
        mixed $extraMail,
        mixed $cc,
        mixed $bcc,
        mixed $message,
        mixed $data,
        mixed $md5,
        mixed $client,
        mixed $hash,
        mixed $project,
        mixed $projectName,
        mixed $projectDate,
        mixed $revision,
        mixed $printVersion,
        mixed $trueReference,
        mixed $temporary,
        mixed $trueFileName,
        mixed $includeSampleFiles,
    ): Response {
        return $this->post('mail_report', 'mail-report', [
            'emails' => json_encode($receivers, JSON_THROW_ON_ERROR),
            'extraMail' => $extraMail,
            'cc' => $cc,
            'bcc' => $bcc,
            'message' => $message,
            'data' => $data,
            'md5' => $md5,
            'client' => $client,
            'hash' => $hash,
            'project' => $project,
            'project_name' => $projectName,
            'project_date' => $projectDate,
            'revision' => $revision,
            'print_version' => $printVersion,
            'true_reference' => $trueReference,
            'temporary' => $temporary,
            'true_file_name' => $trueFileName,
            'includeSampleFiles' => json_encode($includeSampleFiles, JSON_THROW_ON_ERROR),
        ]);
    }

    public function reportMailerNotificationDispatch(mixed $hashes, mixed $client): Response
    {
        return $this->post('notify_reports', 'notify-reports', [
            'hashes' => json_encode($hashes, JSON_THROW_ON_ERROR),
            'client' => $client,
        ]);
    }

    public function updateClient(mixed $clientId): Response
    {
        return $this->post('client_updated', 'client-updated', [
            'mesa_client_id' => $clientId,
        ]);
    }

    public function getContactLists(mixed $clientId): Response
    {
        return $this->get('get_contact_lists', 'contact-lists/'.$clientId);
    }

    public function destroyContactList(mixed $listId): Response
    {
        return $this->get('destroy_contact_list', 'contact-lists/destroy/'.$listId);
    }

    public function createContactList(mixed $clientId, mixed $groupName): Response
    {
        return $this->post('create_contact_list', 'contact-lists', [
            'cliend_id' => $clientId,
            'group_name' => $groupName,
        ]);
    }

    public function updateContactList(mixed $clientId, mixed $groupName, mixed $listId): Response
    {
        return $this->post('update_contact_list', 'contact-lists/update', [
            'list_id' => $listId,
            'cliend_id' => $clientId,
            'group_name' => $groupName,
        ]);
    }

    public function showContactList(mixed $contactList): Response
    {
        return $this->get('show_contact_list', 'contact-lists/show/'.$contactList);
    }

    public function checkClientIsPortalUser(mixed $clientId): Response
    {
        return $this->post('check_client_portal_user', 'client-user-of-portal', [
            'client' => $clientId,
        ]);
    }

    public function dropContact(mixed $contactId): Response
    {
        return $this->get('drop_contact', 'contact-lists/delete-member/'.$contactId);
    }

    public function addContact(mixed $name, mixed $email, mixed $listId): Response
    {
        return $this->post('add_contact', 'contact-lists/add-member', [
            'name' => $name,
            'email' => $email,
            'list_id' => $listId,
        ]);
    }

    public function flushProject(mixed $projectId): Response
    {
        return $this->get('flush_project', 'flush-project/'.$projectId);
    }

    public function forceSync(mixed $projectId): Response
    {
        return $this->get('force_sync', 'force-project/'.$projectId);
    }

    public function getAllContactLists(): Response
    {
        return $this->get('get_all_contact_lists', 'contact-lists/all');
    }

    public function getAllContactListsByLine(): Response
    {
        return $this->get('get_all_contact_lists_by_line', 'contact-lists/all-by-line');
    }

    public function getOrderForm(mixed $portalProjectId): Response
    {
        return $this->post('get_order_form', 'project-order-form', [
            'portal_project_id' => $portalProjectId,
        ]);
    }

    public function updateProductGroup(mixed $sampleId, mixed $productGroupId): Response
    {
        return $this->post('update_product_group', 'update-sample-product-group', [
            'portal_sample_id' => $sampleId,
            'product_group_id' => $productGroupId,
        ]);
    }

    public function swapProjectClient(mixed $portalProjectId, array $mesaProjects): Response
    {
        return $this->post('swap_project_client', 'swap-project-client', [
            'portal_project_id' => $portalProjectId,
            'mesa_project_ids' => implode(',', $mesaProjects),
        ]);
    }

    public function dropSampleFile(mixed $sampleId, mixed $mesaHash): Response
    {
        return $this->post('drop_sample_file', 'drop-sample-file', [
            'mesaHash' => $mesaHash,
            'sampleId' => $sampleId,
        ]);
    }

    public function storeSampleFileInPortal(
        mixed $fileData,
        mixed $mesaHash,
        mixed $dataHash,
        mixed $clientId,
        mixed $sampleId,
        mixed $originalFileName,
    ): Response {
        return $this->post('store_sample_file', 'accept-sample-file', [
            'fileData' => $fileData,
            'mesaHash' => $mesaHash,
            'dataHash' => $dataHash,
            'clientId' => $clientId,
            'sampleId' => $sampleId,
            'originalFileName' => $originalFileName,
        ]);
    }

    public function getReportingPreferenceForProject(mixed $portalProjectId): Response
    {
        return $this->post('get_reporting_preferences', 'report-preferences', [
            'id' => $portalProjectId,
        ]);
    }

    private function get(string $operation, string $endpoint): Response
    {
        return $this->send($operation, 'GET', $endpoint);
    }

    private function post(string $operation, string $endpoint, array $data): Response
    {
        return $this->send($operation, 'POST', $endpoint, $data);
    }

    private function send(string $operation, string $method, string $endpoint, array $data = []): Response
    {
        $requestId = (string) Str::uuid();
        $url = $this->url($endpoint);
        $startedAt = hrtime(true);

        $this->logger->info('Client portal request started.', [
            'portal_request_id' => $requestId,
            'operation' => $operation,
            'method' => $method,
            'url' => $url,
            'field_names' => array_keys($data),
        ]);

        try {
            $response = $method === 'GET'
                ? $this->request()->get($url)
                : $this->request()->asMultipart()->post($url, $data);
        } catch (ConnectionException $exception) {
            $this->logger->error('Client portal connection failed.', [
                'portal_request_id' => $requestId,
                'operation' => $operation,
                'method' => $method,
                'url' => $url,
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error('Client portal request failed before receiving a response.', [
                'portal_request_id' => $requestId,
                'operation' => $operation,
                'method' => $method,
                'url' => $url,
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $context = [
            'portal_request_id' => $requestId,
            'operation' => $operation,
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'response_bytes' => strlen($response->body()),
            'response_content_type' => $response->header('Content-Type'),
            'remote_request_id' => $response->header('X-Request-ID') ?? $response->header('X-Correlation-ID'),
        ];

        if ($response->successful()) {
            $this->settings->set('lastSync', now()->toIso8601String());
            $this->logger->info('Client portal request completed.', $context);
        } else {
            $this->logger->error('Client portal returned an unsuccessful response.', [
                ...$context,
                'response_excerpt' => Str::limit($response->body(), 2000),
            ]);
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->accept($this->settings->acceptType())
            ->withHeaders(['Authorization' => $this->settings->bearer()])
            ->withoutVerifying()
            ->connectTimeout(5)
            ->timeout(30);
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('services.glass.base_url'), '/').'/'.ltrim($endpoint, '/');
    }

    private function durationInMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
