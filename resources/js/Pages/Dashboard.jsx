import { IconUserCircle, IconUsers } from '@/Components/Icons';
import StatCard from '@/Components/UI/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    const { auth } = usePage().props;

    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />

            <div className="mb-6">
                <h2 className="text-2xl font-semibold text-slate-900">
                    Welcome back, {auth.user.name.split(' ')[0]}
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Here&apos;s a snapshot of your workspace.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total Users"
                    value={stats.total_users}
                    accent="indigo"
                    icon={<IconUsers className="h-6 w-6" />}
                />
                <StatCard
                    label="Internal Team"
                    value={stats.internal_users}
                    accent="emerald"
                    icon={<IconUserCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="External Stakeholders"
                    value={stats.external_users}
                    accent="sky"
                    icon={<IconUserCircle className="h-6 w-6" />}
                />
                <StatCard
                    label="Roles Configured"
                    value={stats.roles_configured}
                    accent="amber"
                    icon={<IconUsers className="h-6 w-6" />}
                />
            </div>
        </AuthenticatedLayout>
    );
}
