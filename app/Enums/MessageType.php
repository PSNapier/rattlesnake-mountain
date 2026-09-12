<?php

namespace App\Enums;

enum MessageType: string
{
    case HorseSubmission = 'horse_submission';
    case BreedingResult = 'breeding_result';
    case HorseTransfer = 'horse_transfer';
}
