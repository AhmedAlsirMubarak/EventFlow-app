<?php

namespace App\Enums;

enum OrganizationCategory: string
{
    case GovernmentMinistry = 'Government Ministry';
    case GovernmentAuthority = 'Government Authority';
    case Sponsor = 'Sponsor';
    case CorporatePartner = 'Corporate Partner';
    case Exhibitor = 'Exhibitor';
    case Supplier = 'Supplier';
    case Contractor = 'Contractor';
    case MediaOrganization = 'Media Organization';
    case Venue = 'Venue';
    case ServiceProvider = 'Service Provider';
}
