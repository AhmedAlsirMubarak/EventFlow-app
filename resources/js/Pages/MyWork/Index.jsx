import { IconCalendar, IconCheckCircle, IconClock, IconExclamationTriangle, IconVideoCamera } from '@/Components/Icons';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import StatCard from '@/Components/UI/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, usePoll } from '@inertiajs/react';

const DAY = 24 * 60 * 60 * 1000;

function startOfToday() {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

// 'YYYY-MM-DD' strings are calendar dates — parse them as local midnight rather
// than UTC, or they'd shift a day for anyone west of Greenwich.
function parseDate(value) {
    if (!value) {
        return null;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map(Number);

        return new Date(year, month - 1, day);
    }

    return new Date(value);
}

function dueStatus(value) {
    const date = parseDate(value);

    if (!date) {
        return { label: 'No due date', color: 'slate', overdue: false, thisWeek: false };
    }

    const day = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const diffDays = Math.round((day - startOfToday()) / DAY);
    const formatted = date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });

    if (diffDays < 0) {
        return { label: `Overdue · ${formatted}`, color: 'rose', overdue: true, thisWeek: false };
    }

    if (diffDays === 0) {
        return { label: 'Due today', color: 'amber', overdue: false, thisWeek: true };
    }

    if (diffDays === 1) {
        return { label: 'Due tomorrow', color: 'amber', overdue: false, thisWeek: true };
    }

    if (diffDays <= 7) {
        return {
            label: `Due ${date.toLocaleDateString(undefined, { weekday: 'short' })}`,
            color: 'indigo',
            overdue: false,
            thisWeek: true,
        };
    }

    return { label: `Due ${formatted}`, color: 'slate', overdue: false, thisWeek: false };
}

// "Starts Mon" / "Starts Sep 20" for a task whose start date is still ahead;
// null once it has started (or has no start date).
function startsLabel(value) {
    const date = parseDate(value);

    if (!date || date <= startOfToday()) {
        return null;
    }

    const diffDays = Math.round((date - startOfToday()) / DAY);

    if (diffDays === 1) {
        return 'Starts tomorrow';
    }

    return diffDays <= 7
        ? `Starts ${date.toLocaleDateString(undefined, { weekday: 'short' })}`
        : `Starts ${date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}`;
}

function formatDateTime(value) {
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function eventLink(eventId, tab) {
    return route('events.show', { event: eventId, tab });
}

function Section({ title, count, emptyMessage, children }) {
    return (
        <Card
            title={
                <span className="flex items-center gap-2">
                    {title}
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {count}
                    </span>
                </span>
            }
            className="p-0"
        >
            {count === 0 ? (
                <p className="px-6 py-8 text-center text-sm text-slate-400">{emptyMessage}</p>
            ) : (
                <ul className="divide-y divide-slate-100">{children}</ul>
            )}
        </Card>
    );
}

function Row({ href, title, meta, badge }) {
    return (
        <li>
            <Link
                href={href}
                className="flex items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50 sm:px-6"
            >
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-slate-900">{title}</p>
                    <p className="mt-0.5 truncate text-xs text-slate-500">{meta}</p>
                </div>
                <div className="flex shrink-0 items-center gap-2">{badge}</div>
            </Link>
        </li>
    );
}

export default function Index({ tasks, requests, reminders, meetings, reviews }) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    const dated = [
        ...tasks.map((task) => dueStatus(task.due_date)),
        ...requests.map((request) => dueStatus(request.due_date)),
        ...reminders.map((reminder) => dueStatus(reminder.remind_at)),
    ];
    const overdueCount = dated.filter((status) => status.overdue).length;
    const thisWeekCount = dated.filter((status) => status.thisWeek).length;

    return (
        <AuthenticatedLayout header="My Work">
            <Head title="My Work" />

            <p className="mb-6 text-sm text-slate-500">
                Everything assigned to you, across every event you&apos;re on.
            </p>

            <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Overdue"
                    value={overdueCount}
                    accent="rose"
                    icon={<IconExclamationTriangle className="h-6 w-6" />}
                />
                <StatCard
                    label="Due this week"
                    value={thisWeekCount}
                    accent="amber"
                    icon={<IconClock className="h-6 w-6" />}
                />
                <StatCard
                    label="Upcoming meetings"
                    value={meetings.length}
                    accent="indigo"
                    icon={<IconCalendar className="h-6 w-6" />}
                />
                <StatCard
                    label="Abstracts to review"
                    value={reviews.length}
                    accent="emerald"
                    icon={<IconCheckCircle className="h-6 w-6" />}
                />
            </div>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Section title="My Tasks" count={tasks.length} emptyMessage="No open tasks assigned to you.">
                    {tasks.map((task) => {
                        const due = dueStatus(task.due_date);
                        const starts = startsLabel(task.start_date);

                        return (
                            <Row
                                key={task.id}
                                href={eventLink(task.event_id, 'tasks')}
                                title={task.title}
                                meta={`${task.event.name} · ${task.status} · ${task.priority} priority`}
                                badge={
                                    <>
                                        {starts && <Badge color="slate">{starts}</Badge>}
                                        <Badge color={due.color}>{due.label}</Badge>
                                    </>
                                }
                            />
                        );
                    })}
                </Section>

                <Section title="Upcoming Meetings" count={meetings.length} emptyMessage="No upcoming meetings.">
                    {meetings.map((meeting) => (
                        <Row
                            key={meeting.id}
                            href={eventLink(meeting.event_id, 'meetings')}
                            title={meeting.title}
                            meta={`${meeting.event.name} · ${formatDateTime(meeting.held_at)}${
                                meeting.location ? ` · ${meeting.location}` : ''
                            }`}
                            badge={
                                meeting.meeting_link ? (
                                    <Badge color="indigo">
                                        <IconVideoCamera className="mr-1 h-3.5 w-3.5" />
                                        {meeting.provider ?? 'Online'}
                                    </Badge>
                                ) : null
                            }
                        />
                    ))}
                </Section>

                <Section
                    title="Stakeholder Requests"
                    count={requests.length}
                    emptyMessage="No open requests assigned to you."
                >
                    {requests.map((request) => {
                        const due = dueStatus(request.due_date);
                        const from = request.contact?.full_name ?? request.organization?.name;

                        return (
                            <Row
                                key={request.id}
                                href={eventLink(request.event_id, 'requests')}
                                title={request.title}
                                meta={[request.event.name, from, request.status].filter(Boolean).join(' · ')}
                                badge={<Badge color={due.color}>{due.label}</Badge>}
                            />
                        );
                    })}
                </Section>

                <Section title="Reminders" count={reminders.length} emptyMessage="No pending reminders.">
                    {reminders.map((reminder) => {
                        const due = dueStatus(reminder.remind_at);

                        return (
                            <Row
                                key={reminder.id}
                                href={eventLink(reminder.event_id, 'reminders')}
                                title={reminder.title}
                                meta={`${reminder.event.name} · ${formatDateTime(reminder.remind_at)}`}
                                badge={<Badge color={due.color}>{due.label}</Badge>}
                            />
                        );
                    })}
                </Section>

                {reviews.length > 0 && (
                    <Section title="Abstracts Awaiting Your Review" count={reviews.length}>
                        {reviews.map((review) => (
                            <Row
                                key={review.id}
                                href={eventLink(review.event.id, 'abstracts')}
                                title={review.title}
                                meta={`${review.event.name} · ${review.speaker_name}`}
                                badge={<Badge color="emerald">Review</Badge>}
                            />
                        ))}
                    </Section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
