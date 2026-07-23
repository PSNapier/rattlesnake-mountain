<?php

namespace App\Enums;

enum BreedingSlotTransferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Granted = 'granted';
}
