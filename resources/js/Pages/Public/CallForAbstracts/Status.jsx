import ApplicationLogo from '@/Components/ApplicationLogo';
import Badge from '@/Components/UI/Badge';
import { Head, Link } from '@inertiajs/react';

const statusColors = {
    Submitted: 'slate',
    'Under Review': 'indigo',
    Accepted: 'emerald',
    Rejected: 'rose',
    Waitlisted: 'amber',
    Withdrawn: 'slate',
};

export default function Status({ submission, status }) {
    return (
        <div className="min-h-screen bg-slate-50 py-10">
            <Head title={`Submission Status — ${submission.reference}`} />

            <div className="mx-auto max-w-2xl px-4">
                <div className="mb-6 flex items-center gap-3">
                    <Link href="/">
                        <ApplicationLogo className="h-10 w-10 fill-current text-indigo-600" />
                    </Link>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Submission Status
                    </h1>
                </div>

                {status && (
                    <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-slate-400">
                            {submission.reference} — {submission.call.event.name}
                        </p>
                        <h2 className="mt-1 text-lg font-semibold text-slate-900">
                            {submission.title}
                        </h2>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="text-sm text-slate-500">Status:</span>
                        <Badge color={statusColors[submission.status] ?? 'slate'}>
                            {submission.status}
                        </Badge>
                    </div>

                    {submission.track && (
                        <div>
                            <span className="text-sm text-slate-500">Track: </span>
                            <span className="text-sm text-slate-900">{submission.track.name}</span>
                        </div>
                    )}

                    {submission.decision_notes && (
                        <div>
                            <p className="text-sm text-slate-500">Notes from the committee</p>
                            <p className="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {submission.decision_notes}
                            </p>
                        </div>
                    )}

                    <div className="border-t border-slate-100 pt-4 text-sm text-slate-500">
                        Submitted {new Date(submission.submitted_at).toLocaleString()}
                    </div>
                </div>
            </div>
        </div>
    );
}
