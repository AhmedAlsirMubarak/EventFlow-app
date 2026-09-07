<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'Pending';
    case Completed = 'Completed';
    case Dismissed = 'Dismissed';
}
