<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Requested = 'Requested';
    case InProgress = 'In Progress';
    case WaitingForStakeholder = 'Waiting for Stakeholder';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
