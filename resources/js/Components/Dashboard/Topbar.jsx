import Dropdown from '@/Components/Dropdown';
import { IconBell, IconChevronDown, IconMenu, IconSearch } from '@/Components/Icons';
import UserAvatar from '@/Components/UserAvatar';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Topbar({ user, title, onToggleSidebar, unreadNotificationsCount = 0 }) {
    const { props } = usePage();
    const [searchTerm, setSearchTerm] = useState(props.query ?? '');

    function submitSearch(e) {
        e.preventDefault();
        if (searchTerm.trim().length >= 2) {
            router.get(route('search.index'), { q: searchTerm }, { preserveState: true });
        }
    }

    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={onToggleSidebar}
                    className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                >
                    <IconMenu className="h-5 w-5" />
                </button>
                {title && (
                    <h1 className="text-lg font-semibold text-slate-900">
                        {title}
                    </h1>
                )}
            </div>

            <form onSubmit={submitSearch} className="hidden max-w-sm flex-1 sm:block">
                <div className="relative">
                    <IconSearch className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Search events, documents, contacts…"
                        className="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-sm text-slate-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
            </form>

            <div className="flex items-center gap-2">
                <Link
                    href={route('notifications.index')}
                    className="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                >
                    <IconBell className="h-5 w-5" />
                    {unreadNotificationsCount > 0 && (
                        <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">
                            {unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount}
                        </span>
                    )}
                </Link>

                <Dropdown>
                    <Dropdown.Trigger>
                        <button
                            type="button"
                            className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        >
                            <UserAvatar user={user} size="sm" />
                            <span className="hidden sm:block">{user.name}</span>
                            <IconChevronDown className="h-4 w-4 text-slate-400" />
                        </button>
                    </Dropdown.Trigger>

                    <Dropdown.Content>
                        <Dropdown.Link href={route('profile.edit')}>
                            Profile
                        </Dropdown.Link>
                        <Dropdown.Link
                            href={route('logout')}
                            method="post"
                            as="button"
                        >
                            Log Out
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            </div>
        </header>
    );
}
