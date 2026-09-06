<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdministrator = 'Super Administrator';
    case Management = 'Management';
    case EventDirector = 'Event Director';
    case ProjectManager = 'Project Manager';
    case AccountManager = 'Account Manager';
    case OperationsTeam = 'Operations Team';
    case MarketingTeam = 'Marketing Team';
    case RegistrationTeam = 'Registration Team';
    case SalesSponsorshipTeam = 'Sales / Sponsorship Team';
    case FinanceTeam = 'Finance Team';
    case GeneralStaff = 'General Staff';
    case ExternalStakeholder = 'External Stakeholder';

    /**
     * Roles considered administrative for internal-only gating.
     *
     * @return array<int, self>
     */
    public static function administrative(): array
    {
        return [self::SuperAdministrator, self::Management];
    }
}
