import ApplicationLogo from '@/Components/ApplicationLogo';
import { IconLogout } from '@/Components/Icons';
import UserAvatar from '@/Components/UserAvatar';
import { navigation } from '@/navigation';
import { Link } from '@inertiajs/react';

function isRouteAllowed(item, roles) {
    if (!item.roles) {
        return true;
    }

    return item.roles.some((role) => roles.includes(role));
}

function isActive(routeName) {
    const section = routeName.replace(/\.index$/, '.*');

    return route().current(routeName) || route().current(section);
}

function groupBySection(items) {
    const sections = [];

    items.forEach((item) => {
        const section = sections.find((s) => s.name === item.section);

        if (section) {
            section.items.push(item);
        } else {
            sections.push({ name: item.section, items: [item] });
        }
    });

    return sections;
}

export default function Sidebar({ user, roles, open, onClose }) {
    const items = navigation.filter((item) => isRouteAllowed(item, roles));
    const sections = groupBySection(items);

    return (
        <>
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-sm lg:hidden"
                    onClick={onClose}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 transform flex-col bg-slate-900 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-6">
                    <Link href="/" className="flex items-center gap-2.5">
                        <ApplicationLogo className="h-8 w-8 fill-current text-indigo-400" />
                        <span className="text-lg font-semibold tracking-tight text-white">
                            EventFlow
                        </span>
                    </Link>
                </div>

                <nav className="flex-1 space-y-6 overflow-y-auto px-4 py-6">
                    {sections.map((section) => (
                        <div key={section.name ?? 'default'}>
                            {section.name && (
                                <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    {section.name}
                                </p>
                            )}
                            <div className="space-y-1">
                                {section.items.map((item) => {
                                    const active = isActive(item.routeName);
                                    const Icon = item.icon;

                                    return (
                                        <Link
                                            key={item.routeName}
                                            href={route(item.routeName)}
                                            onClick={onClose}
                                            className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                                active
                                                    ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-900/40'
                                                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                            }`}
                                        >
                                            <Icon
                                                className={`h-5 w-5 shrink-0 ${
                                                    active ? 'text-white' : 'text-slate-400'
                                                }`}
                                            />
                                            {item.name}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </nav>

                <div className="flex shrink-0 items-center gap-1 border-t border-white/10 p-3">
                    <Link
                        href={route('profile.edit')}
                        className="flex min-w-0 flex-1 items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-white/5"
                    >
                        <UserAvatar user={user} size="sm" />
                        <span className="min-w-0 flex-1 text-left">
                            <span className="block truncate text-sm font-medium text-white">
                                {user.name}
                            </span>
                            <span className="block truncate text-xs text-slate-400">
                                {user.email}
                            </span>
                        </span>
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        title="Log out"
                        className="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-white/5 hover:text-white"
                    >
                        <IconLogout className="h-4 w-4" />
                    </Link>
                </div>
            </aside>
        </>
    );
}
