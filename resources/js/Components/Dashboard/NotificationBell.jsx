import { IconBell } from '@/Components/Icons';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

function timeAgo(value) {
    const seconds = Math.max(0, Math.round((Date.now() - new Date(value).getTime()) / 1000));

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 7 * 86400) return `${Math.floor(seconds / 86400)}d ago`;

    return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

/**
 * Top-bar bell: opens a panel with the latest notifications instead of
 * jumping straight to the full page. The list is fetched when the panel
 * opens, so it costs nothing on ordinary page loads.
 */
export default function NotificationBell({ unreadCount = 0 }) {
    const [state, setState] = useState({ status: 'idle', notifications: [] });

    async function load() {
        setState((previous) => ({ ...previous, status: 'loading' }));

        try {
            const response = await fetch(route('notifications.recent'), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const data = await response.json();
            setState({ status: 'ready', notifications: data.notifications });
        } catch {
            setState((previous) => ({ ...previous, status: 'error' }));
        }
    }

    function open(notification, close) {
        close();
        // Marks it read, then the server redirects to whatever it's about.
        router.post(route('notifications.read', notification.id));
    }

    function markAllRead() {
        router.post(route('notifications.read-all'), {}, { preserveScroll: true, preserveState: true, onSuccess: load });
    }

    // Always the live count shared with every page (HandleInertiaRequests), so the
    // badge never goes stale between openings of the panel.
    const badgeCount = unreadCount;

    return (
        <Popover className="relative">
            {({ close }) => (
                <>
                    <PopoverButton
                        onClick={(e) => {
                            // Load on open (the button toggles, so skip when it's closing).
                            if (e.currentTarget.getAttribute('aria-expanded') !== 'true') {
                                load();
                            }
                        }}
                        aria-label={badgeCount > 0 ? `Notifications (${badgeCount} unread)` : 'Notifications'}
                        className="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 focus:outline-none data-open:bg-slate-100 data-open:text-slate-700"
                    >
                        <IconBell className="h-5 w-5" />
                        {badgeCount > 0 && (
                            <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">
                                {badgeCount > 99 ? '99+' : badgeCount}
                            </span>
                        )}
                    </PopoverButton>

                    <PopoverPanel
                        anchor="bottom end"
                        transition
                        className="z-50 w-88 max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl transition duration-150 [--anchor-gap:8px] data-closed:translate-y-1 data-closed:opacity-0"
                    >
                        <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <h2 className="text-sm font-semibold text-slate-900">Notifications</h2>
                            {badgeCount > 0 && state.status === 'ready' && (
                                <button
                                    type="button"
                                    onClick={markAllRead}
                                    className="text-xs font-medium text-indigo-600 hover:text-indigo-500"
                                >
                                    Mark all as read
                                </button>
                            )}
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {state.status === 'loading' && state.notifications.length === 0 && (
                                <div className="space-y-3 p-4">
                                    {[0, 1, 2].map((row) => (
                                        <div key={row} className="space-y-1.5">
                                            <div className="h-3.5 w-3/4 animate-pulse rounded bg-slate-100" />
                                            <div className="h-3 w-1/2 animate-pulse rounded bg-slate-100" />
                                        </div>
                                    ))}
                                </div>
                            )}

                            {state.status === 'error' && (
                                <p className="px-4 py-8 text-center text-sm text-slate-500">
                                    Couldn&apos;t load notifications.{' '}
                                    <button type="button" onClick={load} className="font-medium text-indigo-600">
                                        Try again
                                    </button>
                                </p>
                            )}

                            {state.status === 'ready' && state.notifications.length === 0 && (
                                <div className="flex flex-col items-center gap-2 px-4 py-10 text-center">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <IconBell className="h-5 w-5" />
                                    </span>
                                    <p className="text-sm text-slate-500">You&apos;re all caught up.</p>
                                </div>
                            )}

                            {state.notifications.length > 0 && (
                                <ul className="divide-y divide-slate-100">
                                    {state.notifications.map((notification) => (
                                        <li key={notification.id}>
                                            <button
                                                type="button"
                                                onClick={() => open(notification, close)}
                                                className={`flex w-full gap-3 px-4 py-3 text-left transition hover:bg-slate-50 ${
                                                    notification.read_at ? '' : 'bg-indigo-50/50'
                                                }`}
                                            >
                                                <span
                                                    className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${
                                                        notification.read_at ? 'bg-transparent' : 'bg-indigo-600'
                                                    }`}
                                                    aria-hidden="true"
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span
                                                        className={`block text-sm ${
                                                            notification.read_at
                                                                ? 'text-slate-700'
                                                                : 'font-semibold text-slate-900'
                                                        }`}
                                                    >
                                                        {notification.title}
                                                    </span>
                                                    {notification.body && (
                                                        <span className="mt-0.5 block truncate text-xs text-slate-500">
                                                            {notification.body}
                                                        </span>
                                                    )}
                                                    <span className="mt-1 block text-xs text-slate-400">
                                                        {timeAgo(notification.created_at)}
                                                    </span>
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <Link
                            href={route('notifications.index')}
                            onClick={() => close()}
                            className="block border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center text-sm font-medium text-indigo-600 transition hover:bg-slate-100"
                        >
                            View all notifications
                        </Link>
                    </PopoverPanel>
                </>
            )}
        </Popover>
    );
}
