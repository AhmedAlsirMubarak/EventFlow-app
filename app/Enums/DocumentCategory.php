<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case EventBrochure = 'Event Brochure';
    case FloorPlan = 'Floor Plan';
    case EventAgenda = 'Event Agenda';
    case SponsorshipProposal = 'Sponsorship Proposal';
    case ExhibitorManual = 'Exhibitor Manual';
    case BrandingGuidelines = 'Branding Guidelines';
    case Invitation = 'Invitation';
    case VenueDocumentation = 'Venue Documentation';
    case TechnicalDocument = 'Technical Document';
    case PresentationTemplate = 'Presentation Template';
    case Permit = 'Permit';
    case Contract = 'Contract';
    case EventSchedule = 'Event Schedule';
    case VipInformation = 'VIP Information';
    case OperationalDocument = 'Operational Document';
}
