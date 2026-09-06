import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ users, status }) {
    function destroy(user) {
        if (
            confirm(`Remove ${user.name}? They will lose access immediately.`)
        ) {
            router.delete(route('admin.users.destroy', user.id));
        }
    }

    const columns = [
        { key: 'name', header: 'Name' },
        { key: 'email', header: 'Email' },
        {
            key: 'roles',
            header: 'Roles',
            render: (user) => (
                <div className="flex flex-wrap gap-1">
                    {user.roles.length === 0 && (
                        <span className="text-slate-400">No roles</span>
                    )}
                    {user.roles.map((role) => (
                        <Badge key={role.id} color="indigo">
                            {role.name}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (user) => (
                <div className="flex justify-end gap-3 text-sm">
                    <Link
                        href={route('admin.users.edit', user.id)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        onClick={() => destroy(user)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout header="Users">
            <Head title="Users" />

            {status && (
                <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <Card
                title="Internal team members"
                actions={
                    <Link
                        href={route('admin.users.create')}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New User
                    </Link>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={users}
                    emptyMessage="No internal users yet."
                />
            </Card>
        </AuthenticatedLayout>
    );
}
