<?php

namespace App\Models;

use App\Services\RoleCapabilityService;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
    case Designer = 'designer';
    case StoryAdmin = 'story_admin';
    case GameMaster = 'game_master';

    /**
     * @return list<string>
     */
    public static function areas(): array
    {
        return [
            'submissions',
            'rollers',
            'lifecycle',
            'users',
            'items',
            'shop',
            'cms',
            'design_priority',
            'design_npc',
            'horses',
        ];
    }

    /**
     * Hardcoded defaults used by migration seed and as fallback when DB has no rows.
     *
     * @return list<string>
     */
    public function defaultCapabilities(): array
    {
        return match ($this) {
            self::Admin => [
                'submissions',
                'rollers',
                'lifecycle',
                'users',
                'items',
                'shop',
                'cms',
                'design_priority',
                'design_npc',
                'horses',
            ],
            self::Designer => [
                'submissions',
                'design_priority',
                'design_npc',
            ],
            self::StoryAdmin, self::GameMaster => [
                'rollers',
                'lifecycle',
            ],
            self::User => [],
        };
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return app(RoleCapabilityService::class)->capabilitiesFor($this);
    }

    public function hasCapability(string $area): bool
    {
        return in_array($area, $this->capabilities(), true);
    }

    public function isStaff(): bool
    {
        return $this !== self::User;
    }
}
