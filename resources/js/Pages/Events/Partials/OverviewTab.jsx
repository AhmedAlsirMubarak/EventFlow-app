import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

const statusColors = {
    Planning: 'amber',
    Active: 'emerald',
    Completed: 'slate',
    Cancelled: 'rose',
};

export default function OverviewTab({ event, availableOrganizations }) {
    const [reportOrganizationId, setReportOrganizationId] = useState('');

    const reportUrl = route('events.communication-report', {
        event: event.id,
        ...(reportOrganizationId ? { organization_id: reportOrganizationId } : {}),
    });

    return (
        <div className="space-y-6">
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

            <Card title="Communication Evidence Report">
                <p className="mb-4 text-sm text-slate-600">
                    Export a PDF of every document shared externally for this
                    event, with its full activity trail (shared, viewed,
                    downloaded, acknowledged) and acknowledgment statements —
                    optionally scoped to a single organization.
                </p>
                <div className="flex flex-wrap items-center gap-3">
                    <SelectInput
                        className="w-64"
                        value={reportOrganizationId}
                        onChange={(e) => setReportOrganizationId(e.target.value)}
                    >
                        <option value="">All organizations</option>
                        {availableOrganizations.map((org) => (
                            <option key={org.id} value={org.id}>
                                {org.name}
                            </option>
                        ))}
                    </SelectInput>
                    <a href={reportUrl}>
                        <PrimaryButton type="button">Download Report</PrimaryButton>
                    </a>
                </div>
            </Card>
        </div>
    );
}
