<?php

namespace App\Enums;

enum IdeaStatus: string
{
    case New = 'New';
    case UnderDiscussion = 'Under Discussion';
    case Approved = 'Approved';
    case Planned = 'Planned';
    case Implemented = 'Implemented';
    case Rejected = 'Rejected';
}
