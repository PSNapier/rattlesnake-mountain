<?php

namespace App\Models;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
    case Designer = 'designer';
    case StoryAdmin = 'story_admin';
    case GameMaster = 'game_master';
}
