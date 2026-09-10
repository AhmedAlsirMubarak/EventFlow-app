<?php

namespace App\Enums;

use App\Models\User;

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

    /**
     * Role values the given user is allowed to grant to someone else.
     *
     * Only a Super Administrator may create another Super Administrator — otherwise
     * Management could escalate itself or a colleague to full control.
     *
     * @return array<int, string>
     */
    public static function assignableBy(User $user): array
    {
        $roles = self::cases();

        if (! $user->hasRole(self::SuperAdministrator->value)) {
            $roles = array_filter($roles, fn (self $role) => $role !== self::SuperAdministrator);
        }

        return array_values(array_column($roles, 'value'));
    }
}
