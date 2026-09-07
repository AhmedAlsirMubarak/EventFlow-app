<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'Submitted';
    case UnderReview = 'Under Review';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case Waitlisted = 'Waitlisted';
    case Withdrawn = 'Withdrawn';
}
