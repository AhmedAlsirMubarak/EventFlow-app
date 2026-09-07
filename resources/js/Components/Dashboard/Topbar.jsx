import Dropdown from '@/Components/Dropdown';
import { IconBell, IconChevronDown, IconMenu } from '@/Components/Icons';
import { Link } from '@inertiajs/react';

export default function Topbar({ user, title, onToggleSidebar, unreadNotificationsCount = 0 }) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
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
                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                {user.name.charAt(0).toUpperCase()}
                            </span>
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
