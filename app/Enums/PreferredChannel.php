<?php

namespace App\Enums;

enum PreferredChannel: string
{
    case Email = 'Email';
    case Phone = 'Phone';
    case WhatsApp = 'WhatsApp';
    case Sms = 'SMS';
}
