<?php

namespace App\Keyrings;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;

enum KeyringType: int
{
    case Sample = 0;
    case Project = 1;
    case Export = 2;
    case Extern = 3;

    /**
     * @var list<string>
     */
    public const ROUTABLE_SLUGS = ['sample', 'project', 'export'];

    public static function fromSlug(string $slug): self
    {
        return match ($slug) {
            'sample' => self::Sample,
            'project' => self::Project,
            'export' => self::Export,
            'extern' => self::Extern,
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Sample => 'sample',
            self::Project => 'project',
            self::Export => 'export',
            self::Extern => 'extern',
        };
    }

    public function channelName(int $typeId): string
    {
        return 'keyrings.'.$this->slug().'.'.$typeId;
    }

    public function resourceLabel(): string
    {
        return match ($this) {
            self::Sample => 'Dit monster',
            self::Project => 'Dit project',
            self::Export, self::Extern => 'Deze export',
        };
    }

    /**
     * Mirrors the view permissions of the resource the keyring protects.
     */
    public function isAccessibleBy(User $user, int $typeId): bool
    {
        if (! $user->enabled) {
            return false;
        }

        return match ($this) {
            self::Sample => $user->canAny(['samples.view', 'projects.view'])
                && Sample::query()->whereKey($typeId)->exists(),
            self::Project => $user->can('projects.view')
                && Project::query()->whereKey($typeId)->exists(),
            self::Export, self::Extern => $user->can('projects.view') && $user->can('reports.generate')
                && Project::query()->whereKey($typeId)->exists(),
        };
    }
}
