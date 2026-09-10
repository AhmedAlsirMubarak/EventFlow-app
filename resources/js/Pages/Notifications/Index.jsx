import Card from '@/Components/UI/Card';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, router, usePoll } from '@inertiajs/react';

export default function Index({ notifications }) {
    usePoll(POLL_INTERVAL);

    function openNotification(notification) {
        router.post(route('notifications.read', notification.id));
    }

    function markAllRead() {
        router.post(route('notifications.read-all'));
    }

    const hasUnread = notifications.data.some((notification) => notification.read_at === null);

    return (
        <AuthenticatedLayout header="Notifications">
            <Head title="Notifications" />

            <Card
                title="Notifications"
                actions={
                    hasUnread && (
                        <SecondaryButton type="button" onClick={markAllRead}>
                            Mark All Read
                        </SecondaryButton>
                    )
                }
                className="p-0"
            >
                <ul className="divide-y divide-slate-100">
                    {notifications.data.length === 0 && (
                        <li className="px-4 py-8 text-center text-sm text-slate-400">
                            No notifications yet.
                        </li>
                    )}
                    {notifications.data.map((notification) => (
                        <li key={notification.id}>
                            <button
                                type="button"
                                onClick={() => openNotification(notification)}
                                className={`block w-full px-4 py-3 text-left text-sm hover:bg-slate-50 ${
                                    notification.read_at === null ? 'bg-indigo-50/50' : ''
                                }`}
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <span className="font-medium text-slate-900">
                                        {notification.data.title}
                                    </span>
                                    {notification.read_at === null && (
                                        <span className="h-2 w-2 shrink-0 rounded-full bg-indigo-600" />
                                    )}
                                </div>
                                {notification.data.body && (
                                    <p className="mt-0.5 text-slate-600">{notification.data.body}</p>
                                )}
                                <p className="mt-1 text-xs text-slate-400">
                                    {new Date(notification.created_at).toLocaleString(undefined, {
                                        dateStyle: 'medium',
                                        timeStyle: 'short',
                                    })}
                                </p>
                            </button>
                        </li>
                    ))}
                </ul>
            </Card>

            {notifications.data.length > 0 && (
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    {notifications.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url ?? '#'}
                            className={`rounded-md px-3 py-1.5 text-sm ${
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100'
                            } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
