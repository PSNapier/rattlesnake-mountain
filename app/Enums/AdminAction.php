<?php

namespace App\Enums;

enum AdminAction: string
{
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Archived = 'archived';
}
