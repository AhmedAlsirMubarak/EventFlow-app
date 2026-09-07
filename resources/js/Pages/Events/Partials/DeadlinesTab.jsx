import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';

const typeColors = {
    Meeting: 'indigo',
    Task: 'amber',
    Reminder: 'rose',
    Request: 'emerald',
};

const terminalStatuses = ['Completed', 'Cancelled', 'Dismissed'];

function buildItems({ meetings, tasks, reminders, requests }) {
    const items = [];

    meetings.forEach((meeting) => {
        items.push({
            key: `meeting-${meeting.id}`,
            type: 'Meeting',
            title: meeting.title,
            date: meeting.held_at,
            status: null,
            tab: 'meetings',
        });
    });

    tasks
        .filter((task) => task.due_date)
        .forEach((task) => {
            items.push({
                key: `task-${task.id}`,
                type: 'Task',
                title: task.title,
                date: task.due_date,
                status: task.status,
                tab: 'tasks',
            });
        });

    reminders.forEach((reminder) => {
        items.push({
            key: `reminder-${reminder.id}`,
            type: 'Reminder',
            title: reminder.title,
            date: reminder.remind_at,
            status: reminder.status,
            tab: 'reminders',
        });
    });

    requests
        .filter((request) => request.due_date)
        .forEach((request) => {
            items.push({
                key: `request-${request.id}`,
                type: 'Request',
                title: request.title,
                date: request.due_date,
                status: request.status,
                tab: 'requests',
            });
        });

    return items.sort((a, b) => new Date(a.date) - new Date(b.date));
}

function isOverdue(item) {
    if (item.status && terminalStatuses.includes(item.status)) {
        return false;
    }

    return new Date(item.date) < new Date();
}

export default function DeadlinesTab({ event, meetings, tasks, reminders, requests, onNavigateTab }) {
    const items = buildItems({ meetings, tasks, reminders, requests });

    const columns = [
        {
            key: 'type',
            header: 'Type',
            render: (item) => <Badge color={typeColors[item.type] ?? 'slate'}>{item.type}</Badge>,
        },
        {
            key: 'title',
            header: 'Title',
            render: (item) => (
                <button
                    type="button"
                    onClick={() => onNavigateTab(item.tab)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {item.title}
                </button>
            ),
        },
        {
            key: 'date',
            header: 'Date',
            render: (item) => (
                <span className={isOverdue(item) ? 'font-medium text-rose-600' : ''}>
                    {new Date(item.date).toLocaleString(undefined, {
                        dateStyle: 'medium',
                        timeStyle: item.type === 'Task' || item.type === 'Request' ? undefined : 'short',
                    })}
                    {isOverdue(item) && ' (overdue)'}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (item) => (item.status ? <Badge color="slate">{item.status}</Badge> : '—'),
        },
    ];

    return (
        <Card title="Upcoming Deadlines" className="p-0">
            <DataTable
                columns={columns}
                rows={items}
                emptyMessage="Nothing on the calendar for this event yet."
            />
        </Card>
    );
}
