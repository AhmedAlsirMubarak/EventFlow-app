import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import PortalLayout from '@/Layouts/PortalLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, usePoll } from '@inertiajs/react';

const statusColors = {
    Submitted: 'slate',
    'Under Review': 'indigo',
    Accepted: 'emerald',
    Rejected: 'rose',
    Waitlisted: 'amber',
    Withdrawn: 'slate',
};

const recommendationColors = { Accept: 'emerald', Reject: 'rose', Maybe: 'amber' };

export default function Index({ submissions }) {
    usePoll(POLL_INTERVAL);

    const columns = [
        {
            key: 'title',
            header: 'Submission',
            render: (submission) => (
                <Link
                    href={route('portal.reviews.show', submission.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {submission.title}
                </Link>
            ),
        },
        { key: 'event', header: 'Event', render: (submission) => submission.call.event.name },
        { key: 'speaker_name', header: 'Speaker' },
        { key: 'track', header: 'Track', render: (submission) => submission.track?.name ?? '—' },
        {
            key: 'status',
            header: 'Status',
            render: (submission) => (
                <Badge color={statusColors[submission.status] ?? 'slate'}>{submission.status}</Badge>
            ),
        },
        {
            key: 'my_review',
            header: 'My Review',
            render: (submission) => {
                const review = submission.reviews[0];

                if (!review) {
                    return <span className="text-slate-400">Not reviewed yet</span>;
                }

                return (
                    <span className="flex items-center gap-2">
                        <Badge color="indigo">Score {review.score}/5</Badge>
                        <Badge color={recommendationColors[review.recommendation] ?? 'slate'}>
                            {review.recommendation}
                        </Badge>
                    </span>
                );
            },
        },
    ];

    return (
        <PortalLayout header="My Reviews">
            <Head title="My Reviews" />

            <Card title="Submissions assigned to you" className="p-0">
                <DataTable
                    columns={columns}
                    rows={submissions}
                    emptyMessage="No submissions to review yet."
                />
            </Card>
        </PortalLayout>
    );
}
