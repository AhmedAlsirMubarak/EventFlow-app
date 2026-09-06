<?php

namespace App\Enums;

enum UserType: string
{
    case Internal = 'internal';
    case External = 'external';
}
