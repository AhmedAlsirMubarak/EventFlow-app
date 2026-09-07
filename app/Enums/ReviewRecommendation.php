<?php

namespace App\Enums;

enum ReviewRecommendation: string
{
    case Accept = 'Accept';
    case Reject = 'Reject';
    case Maybe = 'Maybe';
}
