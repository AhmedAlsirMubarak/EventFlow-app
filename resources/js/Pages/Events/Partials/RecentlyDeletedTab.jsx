import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function formatDate(value) {
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function RecentlyDeletedTab({ items }) {
    const [restoringKey, setRestoringKey] = useState(null);

    // `recentlyDeleted` is an optional prop the server skips on normal page loads;
    // fetch it when this tab opens.
    useEffect(() => {
        router.reload({ only: ['recentlyDeleted'] });
    }, []);

    function restore(item) {
        setRestoringKey(item.key);
        router.post(item.restore_url, {}, { preserveScroll: true, onFinish: () => setRestoringKey(null) });
    }

    const columns = [
        { key: 'type', header: 'Type', render: (item) => <Badge color="slate">{item.type}</Badge> },
        { key: 'title', header: 'Title', render: (item) => <span className="font-medium text-slate-900">{item.title}</span> },
        { key: 'deleted_at', header: 'Deleted', render: (item) => formatDate(item.deleted_at) },
        {
            key: 'purges_at',
            header: 'Removed for good',
            render: (item) => (
                <span className="text-slate-500">
                    {new Date(item.purges_at).toLocaleDateString(undefined, { dateStyle: 'medium' })}
                </span>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (item) =>
                item.can_restore ? (
                    <div className="flex justify-end">
                        <button
                            type="button"
                            onClick={() => restore(item)}
                            disabled={restoringKey === item.key}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50"
                        >
                            Restore
                        </button>
                    </div>
                ) : null,
        },
    ];

    return (
        <Card title="Recently deleted" className="p-0">
            <p className="border-b border-slate-100 px-4 py-3 text-sm text-slate-500 sm:px-6">
                Deleted tasks, documents, meetings and other records stay here for 30 days before they&apos;re
                removed for good.
            </p>
            {items === undefined ? (
                <div className="space-y-2 p-6">
                    {[0, 1, 2].map((row) => (
                        <div key={row} className="h-8 animate-pulse rounded bg-slate-100" />
                    ))}
                </div>
            ) : (
                <DataTable columns={columns} rows={items} emptyMessage="Nothing has been deleted from this event." />
            )}
        </Card>
    );
}
