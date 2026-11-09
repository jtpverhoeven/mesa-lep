<?php

namespace App\ClientPortal;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

final class PortalSendOutcome
{
    /**
     * Legacy ignores the document mailer response: only an unreachable portal counts as not delivered.
     *
     * @param  Closure(): Response  $send
     */
    public static function handedOver(Closure $send, string $operation): bool
    {
        try {
            $response = $send();
        } catch (ConnectionException $exception) {
            Log::error('Client portal send failed.', ['operation' => $operation, 'message' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Client portal returned a non-2xx document response.', ['operation' => $operation, 'status' => $response->status()]);
        }

        return true;
    }

    /**
     * Transport failures, non-2xx responses and (when inspected) non-JSON bodies fail the send.
     * Like legacy, a 2xx JSON body only fails when it carries a `message` other than `ok`.
     *
     * @param  Closure(): Response  $send
     */
    public static function succeeded(Closure $send, bool $inspectMessage, string $operation): bool
    {
        try {
            $response = $send();
        } catch (ConnectionException $exception) {
            Log::error('Client portal send failed.', ['operation' => $operation, 'message' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Client portal rejected a send.', ['operation' => $operation, 'status' => $response->status()]);

            return false;
        }

        if (! $inspectMessage) {
            return true;
        }

        $body = $response->json();

        if (! is_array($body)) {
            Log::warning('Client portal returned a non-JSON send response.', ['operation' => $operation]);

            return false;
        }

        $succeeded = ! array_key_exists('message', $body) || $body['message'] === 'ok';

        if (! $succeeded) {
            Log::warning('Client portal reported a send failure.', ['operation' => $operation, 'message' => $body['message']]);
        }

        return $succeeded;
    }
}
