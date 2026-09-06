import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, usePoll } from '@inertiajs/react';

export default function Index({ organizations, status }) {
    usePoll(POLL_INTERVAL);

    const columns = [
        {
            key: 'name',
            header: 'Organization',
            render: (org) => (
                <Link
                    href={route('organizations.show', org.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {org.name}
                </Link>
            ),
        },
        {
            key: 'category',
            header: 'Category',
            render: (org) => <Badge color="indigo">{org.category}</Badge>,
        },
        {
            key: 'account_manager',
            header: 'Account Manager',
            render: (org) => org.account_manager?.name ?? '—',
        },
        {
            key: 'contacts_count',
            header: 'Contacts',
        },
    ];

    return (
        <AuthenticatedLayout header="Organizations">
            <Head title="Organizations" />

            {status && (
                <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <Card
                title="Organizations & Stakeholders"
                actions={
                    <Link
                        href={route('organizations.create')}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New Organization
                    </Link>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={organizations}
                    emptyMessage="No organizations yet."
                />
            </Card>
        </AuthenticatedLayout>
    );
}
