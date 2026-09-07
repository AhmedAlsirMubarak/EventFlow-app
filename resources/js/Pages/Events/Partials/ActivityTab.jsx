import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import TextInput from '@/Components/TextInput';
import { useMemo, useState } from 'react';

function buildItems({ activityLogs, documentActivities }) {
    const items = [];

    activityLogs.forEach((log) => {
        items.push({
            key: `log-${log.id}`,
            description: log.description,
            actor: log.causer?.name ?? 'System',
            at: log.created_at,
        });
    });

    documentActivities.forEach((activity) => {
        items.push({
            key: `doc-${activity.id}`,
            description: `${activity.type} — ${activity.document.title}`,
            actor: activity.user?.name ?? activity.contact?.full_name ?? 'External stakeholder',
            at: activity.created_at,
        });
    });

    return items.sort((a, b) => new Date(b.at) - new Date(a.at));
}

export default function ActivityTab({ activityLogs, documentActivities }) {
    const [actorFilter, setActorFilter] = useState('');
    const [fromDate, setFromDate] = useState('');
    const [toDate, setToDate] = useState('');

    const items = useMemo(
        () => buildItems({ activityLogs, documentActivities }),
        [activityLogs, documentActivities],
    );

    const filtered = items.filter((item) => {
        if (actorFilter && !item.actor.toLowerCase().includes(actorFilter.toLowerCase())) {
            return false;
        }
        if (fromDate && new Date(item.at) < new Date(fromDate)) {
            return false;
        }
        if (toDate && new Date(item.at) > new Date(`${toDate}T23:59:59`)) {
            return false;
        }

        return true;
    });

    const columns = [
        {
            key: 'at',
            header: 'When',
            render: (item) =>
                new Date(item.at).toLocaleString(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }),
        },
        { key: 'actor', header: 'By' },
        { key: 'description', header: 'Activity' },
    ];

    return (
        <Card title="Activity Log" className="p-0">
            <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
                <TextInput
                    className="w-48"
                    placeholder="Filter by person"
                    value={actorFilter}
                    onChange={(e) => setActorFilter(e.target.value)}
                />
                <TextInput
                    type="date"
                    className="w-40"
                    value={fromDate}
                    onChange={(e) => setFromDate(e.target.value)}
                />
                <span className="text-sm text-slate-400">to</span>
                <TextInput
                    type="date"
                    className="w-40"
                    value={toDate}
                    onChange={(e) => setToDate(e.target.value)}
                />
            </div>

            <DataTable
                columns={columns}
                rows={filtered}
                emptyMessage="No activity recorded yet."
            />
        </Card>
    );
}
