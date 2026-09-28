import {
    IconActivity,
    IconBuildingOffice,
    IconCalendar,
    IconCheckCircle,
    IconDashboard,
    IconUsers,
} from '@/Components/Icons';

/**
 * Sidebar navigation entries, grouped under a `section` label for the sidebar
 * to render as separate blocks. `roles`, when present, restricts visibility
 * to users holding at least one of the listed role names; omit it for links
 * every authenticated user should see. Grows as later phases add routes.
 */
export const navigation = [
    {
        name: 'Dashboard',
        routeName: 'dashboard',
        icon: IconDashboard,
        section: 'Workspace',
    },
    {
        name: 'My Work',
        routeName: 'my-work.index',
        icon: IconCheckCircle,
        section: 'Workspace',
    },
    {
        name: 'Events',
        routeName: 'events.index',
        icon: IconCalendar,
        section: 'Workspace',
    },
    {
        name: 'Organizations',
        routeName: 'organizations.index',
        icon: IconBuildingOffice,
        section: 'Workspace',
    },
    {
        name: 'Activity',
        routeName: 'activity.index',
        icon: IconActivity,
        section: 'Workspace',
    },
    {
        name: 'Users',
        routeName: 'admin.users.index',
        icon: IconUsers,
        roles: ['Super Administrator', 'Management'],
        section: 'Administration',
    },
];
