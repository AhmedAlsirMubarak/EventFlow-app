import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link } from '@inertiajs/react';

const statusColors = {
    Shared: 'slate',
    Viewed: 'indigo',
    Downloaded: 'amber',
    Acknowledged: 'emerald',
};

export default function Dashboard({ documents }) {
    const columns = [
        {
            key: 'title',
            header: 'Document',
            render: (doc) => (
                <Link
                    href={route('portal.documents.show', doc.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {doc.title}
                </Link>
            ),
        },
        { key: 'event_name', header: 'Event' },
        { key: 'category', header: 'Category' },
        {
            key: 'latest_status',
            header: 'Status',
            render: (doc) => (
                <Badge color={statusColors[doc.latest_status] ?? 'slate'}>
                    {doc.latest_status ?? 'Shared'}
                </Badge>
            ),
        },
        {
            key: 'acknowledgment_required',
            header: 'Acknowledgment',
            render: (doc) => {
                if (!doc.acknowledgment_required) {
                    return '—';
                }

                return doc.acknowledged ? (
                    <Badge color="emerald">Acknowledged</Badge>
                ) : (
                    <Badge color="amber">Required</Badge>
                );
            },
        },
    ];

    return (
        <PortalLayout header="Shared Documents">
            <Head title="Portal" />

            <Card title="Documents shared with your organization" className="p-0">
                <DataTable
                    columns={columns}
                    rows={documents}
                    emptyMessage="No documents have been shared with you yet."
                />
            </Card>
        </PortalLayout>
    );
}
