import Sidebar from '@/Components/Dashboard/Sidebar';
import Topbar from '@/Components/Dashboard/Topbar';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex min-h-screen bg-slate-50">
            <Sidebar
                roles={auth.roles}
                open={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
            />

            <div className="flex min-w-0 flex-1 flex-col">
                <Topbar
                    user={auth.user}
                    title={header}
                    onToggleSidebar={() => setSidebarOpen((open) => !open)}
                />

                <main className="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
