<?php

namespace App\Enums;

enum BreedingRequestStatus: string
{
    case PendingStaff = 'pending_staff';
    case ResultsReady = 'results_ready';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
