import Card from '@/Components/UI/Card';
import SelectInput from '@/Components/SelectInput';
import StatCard from '@/Components/UI/StatCard';
import TextInput from '@/Components/TextInput';
import {
    IconBuildingOffice,
    IconCalendar,
    IconUserCircle,
    IconUsers,
} from '@/Components/Icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, router, usePage, usePoll } from '@inertiajs/react';
import { useState } from 'react';

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

    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />

            <div className="mb-6">
                <h2 className="text-2xl font-semibold text-slate-900">
                    Welcome back, {auth.user.name.split(' ')[0]}
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Here&apos;s a snapshot of your workspace.
                </p>
            </div>

            <Card className="mb-6">
                <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Event
                        </label>
                        <SelectInput
                            className="mt-1"
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
                        <label className="block text-sm font-medium text-gray-700">
                            Organization
                        </label>
                        <SelectInput
                            className="mt-1"
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
                        <label className="block text-sm font-medium text-gray-700">
                            From
                        </label>
                        <TextInput
                            type="date"
                            className="mt-1"
                            value={form.from}
                            onChange={(e) =>
                                setForm({ ...form, from: e.target.value })
                            }
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            To
                        </label>
                        <TextInput
                            type="date"
                            className="mt-1"
                            value={form.to}
                            onChange={(e) =>
                                setForm({ ...form, to: e.target.value })
                            }
                        />
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
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

            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                Event & Document Activity
            </h3>
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
                />
                <StatCard
                    label="Documents Viewed"
                    value={stats.documents_viewed}
                    accent="sky"
                />
                <StatCard
                    label="Awaiting Acknowledgment"
                    value={stats.documents_awaiting_acknowledgment}
                    accent="amber"
                />
                <StatCard
                    label="Pending Approvals"
                    value={stats.pending_approvals}
                    accent="amber"
                />
                <StatCard
                    label="Open Stakeholder Requests"
                    value={stats.open_stakeholder_requests}
                    accent="amber"
                />
                <StatCard
                    label="Overdue Tasks"
                    value={stats.overdue_tasks}
                    accent="rose"
                />
                <StatCard
                    label="Upcoming Deadlines (7 days)"
                    value={stats.upcoming_deadlines}
                    accent="rose"
                />
            </div>

            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                Team & Roles
            </h3>
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
