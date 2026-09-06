import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const statusColors = {
    Planning: 'amber',
    Active: 'emerald',
    Completed: 'slate',
    Cancelled: 'rose',
};

export default function Index({ events }) {
    const columns = [
        {
            key: 'name',
            header: 'Event',
            render: (event) => (
                <Link
                    href={route('events.show', event.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {event.name}
                </Link>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (event) => (
                <Badge color={statusColors[event.status] ?? 'slate'}>
                    {event.status}
                </Badge>
            ),
        },
        {
            key: 'start_date',
            header: 'Start',
            render: (event) => event.start_date ?? '—',
        },
        {
            key: 'end_date',
            header: 'End',
            render: (event) => event.end_date ?? '—',
        },
        { key: 'users_count', header: 'Team' },
        { key: 'organizations_count', header: 'Organizations' },
    ];

    return (
        <AuthenticatedLayout header="Events">
            <Head title="Events" />

            <Card
                title="Events"
                actions={
                    <Link
                        href={route('events.create')}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New Event
                    </Link>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={events}
                    emptyMessage="No events yet."
                />
            </Card>
        </AuthenticatedLayout>
    );
}
