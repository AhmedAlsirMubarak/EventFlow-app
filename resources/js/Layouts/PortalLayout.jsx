import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { IconChevronDown } from '@/Components/Icons';
import { Link, usePage } from '@inertiajs/react';

export default function PortalLayout({ header, children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <Link href={route('portal.dashboard')} className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-8 fill-current text-indigo-600" />
                        <span className="text-lg font-semibold text-slate-900">
                            EventFlow
                        </span>
                    </Link>

                    <Dropdown>
                        <Dropdown.Trigger>
                            <button
                                type="button"
                                className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >
                                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                    {auth.user.name.charAt(0).toUpperCase()}
                                </span>
                                <span className="hidden sm:block">{auth.user.name}</span>
                                <IconChevronDown className="h-4 w-4 text-slate-400" />
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                Log Out
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </header>

            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                {header && (
                    <h1 className="mb-6 text-2xl font-semibold text-slate-900">
                        {header}
                    </h1>
                )}
                {children}
            </main>
        </div>
    );
}
