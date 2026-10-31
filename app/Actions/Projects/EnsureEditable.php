<?php

namespace App\Actions\Projects;

use App\Actions\Keyrings\AcquireKeyring;
use App\Actions\Keyrings\GetKeyringStatus;
use App\Keyrings\KeyringType;
use App\Keyrings\ResourceNotEditableException;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EnsureEditable
{
    public function __construct(
        private AcquireKeyring $acquire,
        private GetKeyringStatus $keyringStatus,
    ) {}

    /**
     * Call inside the write transaction, after locking the project row.
     * The keyring is claimed (or refreshed) for the authenticated user; console and queue writes skip the keyring.
     *
     * @throws ResourceNotEditableException
     */
    public function handle(?Project $project, ?KeyringType $type = null, ?int $typeId = null, bool $applyProjectRule = true): void
    {
        if ($applyProjectRule && $project?->isReadOnly()) {
            throw ResourceNotEditableException::forProject($project);
        }

        $user = Auth::user();

        if ($type === null || $typeId === null || ! $user instanceof User) {
            return;
        }

        $keyring = $this->acquire->claim($type, $typeId, $user);

        if ($keyring->issued_to !== $user->id) {
            throw ResourceNotEditableException::forKeyring($type, $typeId, $this->keyringStatus->present($keyring, $user));
        }
    }

    /**
     * Lock the sample and its project, then apply the project rule and the sample keyring.
     *
     * @throws ResourceNotEditableException
     */
    public function forSample(int $sampleId): Sample
    {
        $sample = Sample::query()->lockForUpdate()->findOrFail($sampleId);
        $project = (int) $sample->project > 0 ? Project::query()->lockForUpdate()->find($sample->project) : null;
        $this->handle($project, KeyringType::Sample, $sample->id);

        return $sample;
    }
}
