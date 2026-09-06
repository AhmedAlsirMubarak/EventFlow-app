import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import { Link } from '@inertiajs/react';

const statusColors = {
    Planning: 'amber',
    Active: 'emerald',
    Completed: 'slate',
    Cancelled: 'rose',
};

export default function OverviewTab({ event }) {
    return (
        <Card
            title="Event Details"
            actions={
                <Link
                    href={route('events.edit', event.id)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    Edit
                </Link>
            }
        >
            <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt className="text-sm text-slate-500">Status</dt>
                    <dd className="mt-1">
                        <Badge color={statusColors[event.status] ?? 'slate'}>
                            {event.status}
                        </Badge>
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-slate-500">Code</dt>
                    <dd className="mt-1 text-sm text-slate-900">
                        {event.code}
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-slate-500">Start Date</dt>
                    <dd className="mt-1 text-sm text-slate-900">
                        {event.start_date ?? '—'}
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-slate-500">End Date</dt>
                    <dd className="mt-1 text-sm text-slate-900">
                        {event.end_date ?? '—'}
                    </dd>
                </div>
                {event.description && (
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-slate-500">
                            Description
                        </dt>
                        <dd className="mt-1 whitespace-pre-line text-sm text-slate-900">
                            {event.description}
                        </dd>
                    </div>
                )}
            </dl>
        </Card>
    );
}
