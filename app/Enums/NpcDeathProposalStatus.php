<?php

namespace App\Enums;

enum NpcDeathProposalStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Voided = 'voided';
}
