import { IconDashboard, IconUserCircle } from '@/Components/Icons';

/**
 * Sidebar navigation entries. `roles`, when present, restricts visibility to
 * users holding at least one of the listed role names; omit it for links
 * every authenticated user should see. Grows as later phases add routes.
 */
export const navigation = [
    {
        name: 'Dashboard',
        routeName: 'dashboard',
        icon: IconDashboard,
    },
    {
        name: 'My Profile',
        routeName: 'profile.edit',
        icon: IconUserCircle,
    },
];
