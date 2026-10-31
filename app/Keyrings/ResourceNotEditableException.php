<?php

namespace App\Keyrings;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ResourceNotEditableException extends RuntimeException
{
    public const PROJECT_MESSAGE = 'Dit project is vergrendeld of geautoriseerd.';

    /**
     * @param  array<string, mixed>|null  $keyring
     */
    public function __construct(string $message, public readonly string $reason, public readonly ?array $keyring = null)
    {
        parent::__construct($message);
    }

    public static function forProject(Project $project): self
    {
        return new self(self::PROJECT_MESSAGE, $project->isAuthorized() ? 'authorized' : 'authorization_blocked');
    }

    /**
     * @param  array<string, mixed>  $keyring
     */
    public static function forKeyring(KeyringType $type, int $typeId, array $keyring): self
    {
        $holder = $keyring['locked_by_name'] ?? null;
        $message = $holder
            ? $type->resourceLabel().' is in gebruik door '.$holder.'.'
            : $type->resourceLabel().' is in gebruik door een andere gebruiker.';

        return new self($message, 'keyring', [...$keyring, 'type' => $type->slug(), 'type_id' => $typeId]);
    }

    public function report(): bool
    {
        return true;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->withInput()->withErrors(['editability' => $this->getMessage()]);
        }

        return response()->json([
            'message' => $this->getMessage(),
            'reason' => $this->reason,
            'keyring' => $this->keyring,
        ], 423);
    }
}
