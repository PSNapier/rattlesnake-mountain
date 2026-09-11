<?php

namespace App\Enums;

enum AdminAction: string
{
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Archived = 'archived';
    case Unarchived = 'unarchived';
    case PriorityRaised = 'priority_raised';
    case PriorityCleared = 'priority_cleared';
}
