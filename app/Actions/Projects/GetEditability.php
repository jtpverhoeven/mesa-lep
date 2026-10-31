<?php

namespace App\Actions\Projects;

use App\Actions\Keyrings\GetKeyringStatus;
use App\Keyrings\KeyringType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GetEditability
{
    public function __construct(private GetKeyringStatus $keyringStatus) {}

    /**
     * Side-effect free editability for read payloads.
     *
     * @return array{read_only: bool, reasons: list<string>, keyring: array<string, mixed>|null}
     */
    public function handle(?Project $project, ?KeyringType $type = null, ?int $typeId = null, ?User $viewer = null, bool $applyProjectRule = true): array
    {
        $viewer ??= Auth::user();
        $reasons = $applyProjectRule && $project !== null ? $project->readOnlyReasons() : [];
        $keyring = null;

        if ($type !== null && $typeId !== null) {
            $keyring = $this->keyringStatus->handle($type, $typeId, $viewer instanceof User ? $viewer : null);

            if ($keyring['locked']) {
                $reasons[] = 'keyring';
            }
        }

        return [
            'read_only' => $reasons !== [],
            'reasons' => $reasons,
            'keyring' => $keyring,
        ];
    }
}
