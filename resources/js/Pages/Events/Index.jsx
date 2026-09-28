import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import DataTable from '@/Components/UI/DataTable';
import RowActionsMenu from '@/Components/UI/RowActionsMenu';
import EventLogo from '@/Components/EventLogo';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { useConfirm } from '@/utils/useConfirm';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { useState } from 'react';
import DuplicateEventModal from './Partials/DuplicateEventModal';

const statusColors = {
    Planning: 'amber',
    Active: 'emerald',
    Completed: 'slate',
    Cancelled: 'rose',
};

export default function Index({ events }) {
    usePoll(POLL_INTERVAL);
    const [duplicating, setDuplicating] = useState(null);
    const { confirm, dialogProps } = useConfirm();

    function destroy(event) {
        confirm({
            title: `Delete ${event.name}?`,
            message:
                'This permanently deletes the event and everything in it — tasks, documents, meetings, decisions, requests, floor plans and abstract submissions. It cannot be undone.',
            confirmLabel: 'Delete event',
            danger: true,
            requireText: event.name,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('events.destroy', event.id), { preserveScroll: true });
            }
        });
    }

    const columns = [
        {
            key: 'logo',
            header: '',
            render: (event) => <EventLogo event={event} size="md" />,
        },
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
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            render: (event) => (
                <div className="flex justify-end">
                    <RowActionsMenu
                        label={`Actions for ${event.name}`}
                        items={[
                            { label: 'Open', href: route('events.show', event.id) },
                            { label: 'Edit', href: route('events.edit', event.id) },
                            { label: 'Duplicate', onClick: () => setDuplicating(event) },
                            event.can_delete && {
                                label: 'Delete',
                                onClick: () => destroy(event),
                                danger: true,
                                separatorBefore: true,
                            },
                        ]}
                    />
                </div>
            ),
        },
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
                    emptyAction={{ label: 'Create your first event', onClick: () => router.visit(route('events.create')) }}
                />
            </Card>

            {duplicating && (
                <DuplicateEventModal event={duplicating} show onClose={() => setDuplicating(null)} />
            )}

            <ConfirmDialog {...dialogProps} />
        </AuthenticatedLayout>
    );
}
