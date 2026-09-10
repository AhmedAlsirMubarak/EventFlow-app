import Card from '@/Components/UI/Card';
import SelectInput from '@/Components/SelectInput';
import StatCard from '@/Components/UI/StatCard';
import TextInput from '@/Components/TextInput';
import {
    IconBuildingOffice,
    IconCalendar,
    IconCheckCircle,
    IconClock,
    IconDocumentText,
    IconExclamationTriangle,
    IconUserCircle,
    IconUsers,
} from '@/Components/Icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, router, usePage, usePoll } from '@inertiajs/react';
import { useState } from 'react';

function SectionHeader({ children }) {
    return (
        <div className="mb-4 flex items-center gap-2">
            <span className="h-4 w-1 rounded-full bg-indigo-500" />
            <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                {children}
            </h3>
        </div>
    );
}

export default function Dashboard({ stats, filters, filterOptions }) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    const { auth } = usePage().props;
    const [form, setForm] = useState({
        event_id: filters.event_id ?? '',
        organization_id: filters.organization_id ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    function applyFilters(e) {
        e.preventDefault();
        router.get(route('dashboard'), form, { preserveState: true });
    }

    function clearFilters() {
        const cleared = { event_id: '', organization_id: '', from: '', to: '' };
        setForm(cleared);
        router.get(route('dashboard'), {}, { preserveState: true });
    }

    const today = new Date().toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });

    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />

            <div className="mb-6 overflow-hidden rounded-2xl bg-linear-to-br from-indigo-600 via-indigo-600 to-violet-600 shadow-sm">
                <div className="flex flex-col gap-4 px-6 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div>
                        <p className="text-sm font-medium text-indigo-100">{today}</p>
                        <h2 className="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Welcome back, {auth.user.name.split(' ')[0]}
                        </h2>
                        <p className="mt-1 text-sm text-indigo-100">
                            Here&apos;s a snapshot of your workspace.
                        </p>
                    </div>
                    <div className="flex shrink-0 gap-6 sm:gap-8">
                        <div>
                            <p className="text-2xl font-bold text-white sm:text-3xl">
                                {stats.active_events}
                            </p>
                            <p className="text-xs font-medium text-indigo-100">Active Events</p>
                        </div>
                        <div>
                            <p className="text-2xl font-bold text-white sm:text-3xl">
                                {stats.overdue_tasks + stats.upcoming_deadlines}
                            </p>
                            <p className="text-xs font-medium text-indigo-100">Needs Attention</p>
                        </div>
                    </div>
                </div>
            </div>

            <Card className="mb-8">
                <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-4">
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Event
                        </label>
                        <SelectInput
                            className="mt-1.5"
                            value={form.event_id}
                            onChange={(e) =>
                                setForm({ ...form, event_id: e.target.value })
                            }
                        >
                            <option value="">All events</option>
                            {filterOptions.events.map((event) => (
                                <option key={event.id} value={event.id}>
                                    {event.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Organization
                        </label>
                        <SelectInput
                            className="mt-1.5"
                            value={form.organization_id}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    organization_id: e.target.value,
                                })
                            }
                        >
                            <option value="">All organizations</option>
                            {filterOptions.organizations.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            From
                        </label>
                        <TextInput
                            type="date"
                            className="mt-1.5"
                            value={form.from}
                            onChange={(e) =>
                                setForm({ ...form, from: e.target.value })
                            }
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            To
                        </label>
                        <TextInput
                            type="date"
                            className="mt-1.5"
                            value={form.to}
                            onChange={(e) =>
                                setForm({ ...form, to: e.target.value })
                            }
                        />
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                    >
                        Apply
                    </button>
                    <button
                        type="button"
                        onClick={clearFilters}
                        className="text-sm font-medium text-slate-500 hover:text-slate-700"
                    >
                        Clear
                    </button>
                </form>
            </Card>

            <SectionHeader>Event &amp; Document Activity</SectionHeader>
            <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard
                    label="Active Events"
                    value={stats.active_events}
                    accent="indigo"
                    icon={<IconCalendar className="h-6 w-6" />}
                />
                <StatCard
                    label="Participating Organizations"
                    value={stats.participating_organizations}
                    accent="sky"
                    icon={<IconBuildingOffice className="h-6 w-6" />}
                />
                <StatCard
                    label="Total Stakeholders"
                    value={stats.total_stakeholders}
                    accent="emerald"
                    icon={<IconUserCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="Documents Shared"
                    value={stats.documents_shared}
                    accent="indigo"
                    icon={<IconDocumentText className="h-6 w-6" />}
                />
                <StatCard
                    label="Documents Viewed"
                    value={stats.documents_viewed}
                    accent="sky"
                    icon={<IconDocumentText className="h-6 w-6" />}
                />
                <StatCard
                    label="Awaiting Acknowledgment"
                    value={stats.documents_awaiting_acknowledgment}
                    accent="amber"
                    icon={<IconClock className="h-6 w-6" />}
                />
                <StatCard
                    label="Pending Approvals"
                    value={stats.pending_approvals}
                    accent="amber"
                    icon={<IconCheckCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="Open Stakeholder Requests"
                    value={stats.open_stakeholder_requests}
                    accent="amber"
                    icon={<IconClock className="h-6 w-6" />}
                />
                <StatCard
                    label="Overdue Tasks"
                    value={stats.overdue_tasks}
                    accent="rose"
                    icon={<IconExclamationTriangle className="h-6 w-6" />}
                />
                <StatCard
                    label="Upcoming Deadlines (7 days)"
                    value={stats.upcoming_deadlines}
                    accent="rose"
                    icon={<IconClock className="h-6 w-6" />}
                />
            </div>

            <SectionHeader>Team &amp; Roles</SectionHeader>
            <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total Users"
                    value={stats.total_users}
                    accent="indigo"
                    icon={<IconUsers className="h-6 w-6" />}
                />
                <StatCard
                    label="Internal Team"
                    value={stats.internal_users}
                    accent="emerald"
                    icon={<IconUserCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="External Stakeholders"
                    value={stats.external_users}
                    accent="sky"
                    icon={<IconUserCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="Roles Configured"
                    value={stats.roles_configured}
                    accent="amber"
                    icon={<IconUsers className="h-6 w-6" />}
                />
            </div>
        </AuthenticatedLayout>
    );
}
