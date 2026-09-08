import Card from '@/Components/UI/Card';
import SelectInput from '@/Components/SelectInput';
import UserAvatar from '@/Components/UserAvatar';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

function formatDateTime(value) {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function Index({ activity, filters, filterOptions }) {
    function filterByEvent(e) {
        router.get(
            route('activity.index'),
            e.target.value ? { event_id: e.target.value } : {},
            { preserveState: true },
        );
    }

    return (
        <AuthenticatedLayout header="Activity">
            <Head title="Activity" />

            <Card
                title="Recent Activity"
                actions={
                    <SelectInput value={filters.event_id ?? ''} onChange={filterByEvent}>
                        <option value="">All events</option>
                        {filterOptions.events.map((event) => (
                            <option key={event.id} value={event.id}>
                                {event.name}
                            </option>
                        ))}
                    </SelectInput>
                }
            >
                {activity.length === 0 ? (
                    <p className="text-sm text-slate-400">No activity recorded yet.</p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {activity.map((item, index) => (
                            <li key={index} className="flex items-center justify-between py-3">
                                <div className="flex items-center gap-3">
                                    {item.actor && (
                                        <UserAvatar
                                            user={{ name: item.actor, avatar_url: item.actor_avatar_url }}
                                            size="xs"
                                        />
                                    )}
                                    <div>
                                        <p className="text-sm text-slate-900">{item.description}</p>
                                        {item.actor && (
                                            <p className="text-xs text-slate-500">by {item.actor}</p>
                                        )}
                                    </div>
                                </div>
                                <span className="text-xs text-slate-400">
                                    {formatDateTime(item.created_at)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </AuthenticatedLayout>
    );
}
