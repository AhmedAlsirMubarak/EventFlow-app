import ApplicationLogo from '@/Components/ApplicationLogo';
import { navigation } from '@/navigation';
import { Link } from '@inertiajs/react';

function isRouteAllowed(item, roles) {
    if (!item.roles) {
        return true;
    }

    return item.roles.some((role) => roles.includes(role));
}

export default function Sidebar({ roles, open, onClose }) {
    const items = navigation.filter((item) => isRouteAllowed(item, roles));

    return (
        <>
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                    onClick={onClose}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 transform flex-col bg-slate-900 transition-transform lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 shrink-0 items-center gap-2 px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-8 fill-current text-indigo-400" />
                        <span className="text-lg font-semibold text-white">
                            EventFlow
                        </span>
                    </Link>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    {items.map((item) => {
                        const active = route().current(item.routeName);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.routeName}
                                href={route(item.routeName)}
                                className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ${
                                    active
                                        ? 'bg-indigo-500/10 text-white'
                                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                }`}
                            >
                                <Icon
                                    className={`h-5 w-5 ${active ? 'text-indigo-400' : 'text-slate-400'}`}
                                />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
            </aside>
        </>
    );
}
