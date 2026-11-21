<?php

namespace App\Actions\AuditTrail;

use App\Actions\ChangeTracking\GetChangeRevisions;
use App\ChangeTracking\ChangeType;
use App\Models\ChangeRevision;
use App\Support\LegacyTimestamp;
use App\Support\UserNames;

class ListAuditRevisions
{
    public function __construct(private GetChangeRevisions $revisions, private UserNames $users) {}

    /**
     * Every revision of a project, sample, analysis or assurance form, oldest first (newest first for assurance
     * forms, as legacy listed them).
     *
     * @return list<array{id: int, at: int, timestamp: string, user: string, type_code: string, type: string, event: string, from: string, to: string}>
     */
    public function handle(string $scope, int $id, ?int $sampleId = null): array
    {
        return $this->revisions->query($scope, $id, $sampleId)
            ->orderBy('id', $scope === 'assurance-form' ? 'desc' : 'asc')
            ->get()
            ->map(fn (ChangeRevision $revision): array => [
                'id' => (int) $revision->id,
                'at' => (int) $revision->timestamp,
                'timestamp' => LegacyTimestamp::format($revision->timestamp, 'd-m-Y H:i:s') ?? '',
                'user' => $this->users->name($revision->user_id),
                'type_code' => (string) $revision->type,
                'type' => ChangeType::label($revision->type),
                'event' => (string) $revision->event,
                'from' => (string) $revision->from,
                'to' => (string) $revision->to,
            ])
            ->all();
    }
}
