<?php

namespace App\Enums;

enum BreedingSlotStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Consumed = 'consumed';
}
