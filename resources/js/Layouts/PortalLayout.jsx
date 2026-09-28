import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { IconChevronDown, IconMenu } from '@/Components/Icons';
import BackLink from '@/Components/UI/BackLink';
import FlashBanner from '@/Components/UI/FlashBanner';
import UserAvatar from '@/Components/UserAvatar';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const navLinkClass = 'text-sm font-medium text-slate-600 hover:text-slate-900';
const mobileNavLinkClass = 'rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100';

export default function PortalLayout({ header, back, children }) {
    const { auth, hasCommitteeAssignments } = usePage().props;
    const [mobileNavOpen, setMobileNavOpen] = useState(false);

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-8">
                        <Link href={route('portal.dashboard')} className="flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-8 fill-current text-indigo-600" />
                            <span className="text-lg font-semibold text-slate-900">
                                EventFlow
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-6 sm:flex">
                            <Link href={route('portal.dashboard')} className={navLinkClass}>
                                Documents
                            </Link>
                            <Link href={route('portal.requests.index')} className={navLinkClass}>
                                My Requests
                            </Link>
                            {hasCommitteeAssignments && (
                                <Link href={route('portal.reviews.index')} className={navLinkClass}>
                                    My Reviews
                                </Link>
                            )}
                        </nav>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setMobileNavOpen((prev) => !prev)}
                            className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 sm:hidden"
                        >
                            <IconMenu className="h-5 w-5" />
                        </button>

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                                >
                                    <UserAvatar user={auth.user} size="sm" />
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
                </div>

                {mobileNavOpen && (
                    <nav className="flex flex-col gap-1 border-t border-slate-200 px-4 py-3 sm:hidden">
                        <Link
                            href={route('portal.dashboard')}
                            onClick={() => setMobileNavOpen(false)}
                            className={mobileNavLinkClass}
                        >
                            Documents
                        </Link>
                        <Link
                            href={route('portal.requests.index')}
                            onClick={() => setMobileNavOpen(false)}
                            className={mobileNavLinkClass}
                        >
                            My Requests
                        </Link>
                        {hasCommitteeAssignments && (
                            <Link
                                href={route('portal.reviews.index')}
                                onClick={() => setMobileNavOpen(false)}
                                className={mobileNavLinkClass}
                            >
                                My Reviews
                            </Link>
                        )}
                    </nav>
                )}
            </header>

            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                {back && <BackLink href={back.href} label={back.label} />}
                {header && (
                    <h1 className="mb-6 text-2xl font-semibold text-slate-900">
                        {header}
                    </h1>
                )}
                <FlashBanner />
                {children}
            </main>
        </div>
    );
}
