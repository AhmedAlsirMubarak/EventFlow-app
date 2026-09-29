import BarList from '@/Components/Charts/BarList';
import TrendChart from '@/Components/Charts/TrendChart';
import EventLogo from '@/Components/EventLogo';
import {
    IconCalendar,
    IconCheckCircle,
    IconClock,
    IconDocumentText,
    IconInbox,
} from '@/Components/Icons';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import StatCard from '@/Components/UI/StatCard';
import UserAvatar from '@/Components/UserAvatar';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { useState } from 'react';

const DAY = 24 * 60 * 60 * 1000;

function parseDate(value) {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function daysFromToday(value) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    return Math.round((parseDate(value) - today) / DAY);
}

function shortDate(value) {
    return parseDate(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function eventCountdown(event) {
    if (!event.start_date) {
        return { label: 'Date to be confirmed', color: 'slate' };
    }

    const startsIn = daysFromToday(event.start_date);

    if (startsIn <= 0) {
        return { label: 'Happening now', color: 'emerald' };
    }

    if (startsIn === 1) {
        return { label: 'Starts tomorrow', color: 'amber' };
    }

    return { label: `In ${startsIn} days`, color: startsIn <= 14 ? 'amber' : 'indigo' };
}

function dueBadge(value) {
    const days = daysFromToday(value);

    if (days < 0) {
        return { label: `${-days}d overdue`, color: 'rose' };
    }

    if (days === 0) {
        return { label: 'Due today', color: 'amber' };
    }

    return { label: `Due ${shortDate(value)}`, color: days <= 2 ? 'amber' : 'slate' };
}

function Panel({ title, action, children, className = '' }) {
    return (
        <section className={`flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm ${className}`}>
            <header className="flex items-center justify-between gap-3 px-5 pt-5">
                <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                {action}
            </header>
            <div className="flex-1 p-5">{children}</div>
        </section>
    );
}

function EmptyNote({ children }) {
    return (
        <div className="flex h-full flex-col items-center justify-center gap-2 py-8 text-center">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <IconInbox className="h-5 w-5" />
            </span>
            <p className="text-sm text-slate-400">{children}</p>
        </div>
    );
}

export default function Dashboard({ stats, charts, upcomingEvents, attentionTasks, filters, filterOptions }) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    const { auth } = usePage().props;
    const [trendView, setTrendView] = useState('chart');
    const [form, setForm] = useState({
        event_id: filters.event_id ?? '',
        organization_id: filters.organization_id ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    const isFiltered = Object.values(filters).some(Boolean);
    const trendTotal = charts.activity_trend.reduce((sum, point) => sum + point.count, 0);
    const totalTasks = charts.tasks_by_status.reduce((sum, row) => sum + row.count, 0);

    function applyFilters(e) {
        e.preventDefault();
        router.get(route('dashboard'), form, { preserveState: true, preserveScroll: true });
    }

    function clearFilters() {
        setForm({ event_id: '', organization_id: '', from: '', to: '' });
        router.get(route('dashboard'), {}, { preserveState: true, preserveScroll: true });
    }

    const today = new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    const needsAttention = stats.overdue_tasks + stats.pending_approvals;

    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />

            {/* Hero */}
            <div className="mb-6 overflow-hidden rounded-2xl bg-linear-to-br from-indigo-600 via-indigo-600 to-violet-600 shadow-sm">
                <div className="flex flex-col gap-5 px-6 py-7 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div>
                        <p className="text-sm font-medium text-indigo-100">{today}</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            {greeting}, {auth.user.name.split(' ')[0]}
                        </h1>
                        <p className="mt-1 text-sm text-indigo-100">
                            {needsAttention > 0
                                ? `${needsAttention} ${needsAttention === 1 ? 'item needs' : 'items need'} your attention today.`
                                : 'Everything is on track — nothing overdue or waiting on approval.'}
                        </p>
                    </div>
                    <Link
                        href={route('my-work.index')}
                        className="inline-flex shrink-0 items-center justify-center rounded-lg bg-white/15 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/25 backdrop-blur transition hover:bg-white/25"
                    >
                        Open My Work
                    </Link>
                </div>
            </div>

            {/* Filters: one row, scoping everything below */}
            <form
                onSubmit={applyFilters}
                className="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div className="min-w-44 flex-1">
                    <label htmlFor="filter_event_id" className="block text-xs font-medium text-slate-500">
                        Event
                    </label>
                    <SelectInput
                        id="filter_event_id"
                        className="mt-1 w-full"
                        value={form.event_id}
                        onChange={(e) => setForm({ ...form, event_id: e.target.value })}
                    >
                        <option value="">All events</option>
                        {filterOptions.events.map((event) => (
                            <option key={event.id} value={event.id}>
                                {event.name}
                            </option>
                        ))}
                    </SelectInput>
                </div>
                <div className="min-w-44 flex-1">
                    <label htmlFor="filter_organization_id" className="block text-xs font-medium text-slate-500">
                        Organization
                    </label>
                    <SelectInput
                        id="filter_organization_id"
                        className="mt-1 w-full"
                        value={form.organization_id}
                        onChange={(e) => setForm({ ...form, organization_id: e.target.value })}
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
                    <label htmlFor="filter_from" className="block text-xs font-medium text-slate-500">
                        From
                    </label>
                    <TextInput
                        id="filter_from"
                        type="date"
                        className="mt-1"
                        value={form.from}
                        onChange={(e) => setForm({ ...form, from: e.target.value })}
                    />
                </div>
                <div>
                    <label htmlFor="filter_to" className="block text-xs font-medium text-slate-500">
                        To
                    </label>
                    <TextInput
                        id="filter_to"
                        type="date"
                        className="mt-1"
                        value={form.to}
                        onChange={(e) => setForm({ ...form, to: e.target.value })}
                    />
                </div>
                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                    >
                        Apply
                    </button>
                    {isFiltered && (
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="text-sm font-medium text-slate-500 hover:text-slate-700"
                        >
                            Clear
                        </button>
                    )}
                </div>
            </form>

            {/* Headline numbers */}
            <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <StatCard
                    label="Active events"
                    value={stats.active_events}
                    accent="indigo"
                    icon={<IconCalendar className="h-6 w-6" />}
                    hint={`${stats.participating_organizations} participating organizations`}
                    href={route('events.index')}
                />
                <StatCard
                    label="Open tasks"
                    value={stats.open_tasks}
                    accent="emerald"
                    icon={<IconCheckCircle className="h-6 w-6" />}
                    hint={
                        stats.overdue_tasks > 0
                            ? `${stats.overdue_tasks} overdue · ${stats.upcoming_deadlines} due this week`
                            : `${stats.upcoming_deadlines} due this week`
                    }
                    hintTone={stats.overdue_tasks > 0 ? 'alert' : 'muted'}
                    href={route('my-work.index')}
                />
                <StatCard
                    label="Pending approvals"
                    value={stats.pending_approvals}
                    accent="amber"
                    icon={<IconDocumentText className="h-6 w-6" />}
                    hint="Documents awaiting review"
                />
                <StatCard
                    label="Open requests"
                    value={stats.open_stakeholder_requests}
                    accent="sky"
                    icon={<IconClock className="h-6 w-6" />}
                    hint={`${stats.total_stakeholders} stakeholder contacts`}
                />
            </div>

            {/* Trend + task breakdown */}
            <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Panel
                    className="lg:col-span-2"
                    title="Workspace activity"
                    action={
                        <div className="flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium" role="group">
                            {['chart', 'table'].map((view) => (
                                <button
                                    key={view}
                                    type="button"
                                    onClick={() => setTrendView(view)}
                                    aria-pressed={trendView === view}
                                    className={`rounded-md px-2.5 py-1 capitalize transition ${
                                        trendView === view ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                    }`}
                                >
                                    {view}
                                </button>
                            ))}
                        </div>
                    }
                >
                    <p className="mb-4 text-sm text-slate-500">
                        <span className="text-2xl font-bold text-slate-900">{trendTotal}</span>{' '}
                        {trendTotal === 1 ? 'update' : 'updates'} logged in the last 30 days
                    </p>
                    {trendView === 'chart' ? (
                        <TrendChart points={charts.activity_trend} unit="update" unitPlural="updates" />
                    ) : (
                        <div className="max-h-48 overflow-y-auto rounded-lg border border-slate-100">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 bg-slate-50 text-left text-xs text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2 font-medium">Day</th>
                                        <th className="px-3 py-2 text-right font-medium">Updates</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {[...charts.activity_trend].reverse().map((point) => (
                                        <tr key={point.date}>
                                            <td className="px-3 py-1.5 text-slate-600">
                                                {parseDate(point.date).toLocaleDateString(undefined, {
                                                    weekday: 'short',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })}
                                            </td>
                                            <td className="px-3 py-1.5 text-right tabular-nums text-slate-900">
                                                {point.count}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Panel>

                <Panel title="Tasks by status" action={<span className="text-sm text-slate-400">{totalTasks} total</span>}>
                    {totalTasks === 0 ? (
                        <EmptyNote>No tasks yet.</EmptyNote>
                    ) : (
                        <BarList
                            items={charts.tasks_by_status.map((row) => ({ label: row.status, value: row.count }))}
                            scaleTo={totalTasks}
                            shareOf={totalTasks}
                            shareLabel="of tasks"
                        />
                    )}
                </Panel>
            </div>

            {/* What needs doing */}
            <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Panel
                    className="lg:col-span-2"
                    title="Needs attention"
                    action={
                        <Link href={route('my-work.index')} className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            View all
                        </Link>
                    }
                >
                    {attentionTasks.length === 0 ? (
                        <EmptyNote>Nothing overdue or due in the next 7 days.</EmptyNote>
                    ) : (
                        <ul className="-mx-2 divide-y divide-slate-100">
                            {attentionTasks.map((task) => {
                                const due = dueBadge(task.due_date);

                                return (
                                    <li key={task.id}>
                                        <Link
                                            href={route('events.show', { event: task.event_id, tab: 'tasks' })}
                                            className="flex items-center gap-3 rounded-lg px-2 py-2.5 transition hover:bg-slate-50"
                                        >
                                            {task.assignee ? (
                                                <UserAvatar user={task.assignee} size="sm" />
                                            ) : (
                                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 text-xs text-slate-400">
                                                    ?
                                                </span>
                                            )}
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-slate-900">{task.title}</p>
                                                <p className="truncate text-xs text-slate-500">
                                                    {task.event.name} · {task.assignee?.name ?? 'Unassigned'}
                                                </p>
                                            </div>
                                            <Badge color={due.color}>{due.label}</Badge>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Panel>

                <Panel
                    title="Upcoming events"
                    action={
                        <Link href={route('events.index')} className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            All events
                        </Link>
                    }
                >
                    {upcomingEvents.length === 0 ? (
                        <EmptyNote>No upcoming events.</EmptyNote>
                    ) : (
                        <ul className="-mx-2 space-y-1">
                            {upcomingEvents.map((event) => {
                                const countdown = eventCountdown(event);

                                return (
                                    <li key={event.id}>
                                        <Link
                                            href={route('events.show', event.id)}
                                            className="flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50"
                                        >
                                            <EventLogo event={event} size="sm" />
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-slate-900">{event.name}</p>
                                                <p className="text-xs text-slate-500">
                                                    {event.start_date ? shortDate(event.start_date) : 'TBC'}
                                                    {event.end_date && event.end_date !== event.start_date
                                                        ? ` – ${shortDate(event.end_date)}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Badge color={countdown.color}>{countdown.label}</Badge>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Panel>
            </div>

            {/* Engagement + people */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Panel className="lg:col-span-2" title="Document engagement">
                    {stats.documents_shared === 0 ? (
                        <EmptyNote>No documents shared with stakeholders yet.</EmptyNote>
                    ) : (
                        <>
                            <BarList
                                items={[
                                    { label: 'Shared with stakeholders', value: stats.documents_shared },
                                    { label: 'Viewed', value: stats.documents_viewed },
                                    { label: 'Acknowledged', value: stats.documents_acknowledged },
                                ]}
                                scaleTo={stats.documents_shared}
                                shareOf={stats.documents_shared}
                                shareLabel="of shared"
                            />
                            {stats.documents_awaiting_acknowledgment > 0 && (
                                <p className="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    {stats.documents_awaiting_acknowledgment}{' '}
                                    {stats.documents_awaiting_acknowledgment === 1 ? 'document is' : 'documents are'} still
                                    waiting for a stakeholder to acknowledge.
                                </p>
                            )}
                        </>
                    )}
                </Panel>

                <Panel title="People">
                    <dl className="grid grid-cols-2 gap-4">
                        {[
                            ['Internal team', stats.internal_users],
                            ['External users', stats.external_users],
                            ['Stakeholder contacts', stats.total_stakeholders],
                            ['Organizations', stats.participating_organizations],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-xl bg-slate-50 px-4 py-3">
                                <dt className="text-xs text-slate-500">{label}</dt>
                                <dd className="mt-1 text-xl font-bold text-slate-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </Panel>
            </div>
        </AuthenticatedLayout>
    );
}
