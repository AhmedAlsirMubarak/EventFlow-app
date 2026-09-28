import Sidebar from '@/Components/Dashboard/Sidebar';
import Topbar from '@/Components/Dashboard/Topbar';
import BackLink from '@/Components/UI/BackLink';
import FlashBanner from '@/Components/UI/FlashBanner';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * `back` ({ href, label }): a "← Back to …" link above the page, for detail and
 * form pages that aren't reachable from the sidebar directly.
 */
export default function AuthenticatedLayout({ header, back, children }) {
    const { auth, unreadNotificationsCount } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex min-h-screen bg-slate-50">
            <Sidebar
                user={auth.user}
                roles={auth.roles}
                open={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
            />

            <div className="flex min-w-0 flex-1 flex-col">
                <Topbar
                    user={auth.user}
                    title={header}
                    onToggleSidebar={() => setSidebarOpen((open) => !open)}
                    unreadNotificationsCount={unreadNotificationsCount}
                />

                <main className="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {back && <BackLink href={back.href} label={back.label} />}
                    <FlashBanner />
                    {children}
                </main>
            </div>
        </div>
    );
}
