<?php

namespace App\Models;

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
    public function capabilities(): array
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
            ],
            self::Designer => [
                'submissions',
            ],
            self::StoryAdmin, self::GameMaster => [
                'rollers',
                'lifecycle',
            ],
            self::User => [],
        };
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
