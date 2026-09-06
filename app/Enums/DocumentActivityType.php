<?php

namespace App\Enums;

enum DocumentActivityType: string
{
    case Shared = 'Shared';
    case Viewed = 'Viewed';
    case Downloaded = 'Downloaded';
    case Acknowledged = 'Acknowledged';
}
