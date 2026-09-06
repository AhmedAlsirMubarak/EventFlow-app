<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'Backlog';
    case Planned = 'Planned';
    case InProgress = 'In Progress';
    case WaitingBlocked = 'Waiting / Blocked';
    case Completed = 'Completed';
}
