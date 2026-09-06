<?php

namespace App\Enums;

enum EventStatus: string
{
    case Planning = 'Planning';
    case Active = 'Active';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
