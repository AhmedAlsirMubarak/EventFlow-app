import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const statusColors = {
    Submitted: 'slate',
    'Under Review': 'indigo',
    Accepted: 'emerald',
    Rejected: 'rose',
    Waitlisted: 'amber',
    Withdrawn: 'slate',
};

export default function Show({ submission }) {
    const myReview = submission.reviews[0] ?? null;

    const form = useForm({
        score: myReview?.score ?? 3,
        recommendation: myReview?.recommendation ?? 'Accept',
        comments: myReview?.comments ?? '',
    });

    function submit(e) {
        e.preventDefault();
        form.post(route('portal.reviews.store', submission.id), { preserveScroll: true });
    }

    return (
        <PortalLayout header="Submission Review">
            <Head title={submission.title} />

            <div className="space-y-6">
                <Card>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">
                                {submission.reference} — {submission.call.event.name}
                            </p>
                            <h2 className="text-lg font-semibold text-slate-900">{submission.title}</h2>
                        </div>
                        <Badge color={statusColors[submission.status] ?? 'slate'}>{submission.status}</Badge>
                    </div>

                    <div className="mt-4">
                        <h3 className="text-sm font-medium text-slate-500">Abstract</h3>
                        <p className="mt-1 whitespace-pre-line text-sm text-slate-700">
                            {submission.abstract_body}
                        </p>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4 rounded-lg bg-slate-50 p-3 text-sm">
                        <div>
                            <span className="text-slate-500">Speaker: </span>
                            {submission.speaker_name}
                        </div>
                        <div>
                            <span className="text-slate-500">Track: </span>
                            {submission.track?.name ?? '—'}
                        </div>
                        {submission.speaker_organization && (
                            <div>
                                <span className="text-slate-500">Organization: </span>
                                {submission.speaker_organization}
                            </div>
                        )}
                        {submission.speaker_bio && (
                            <div className="col-span-2">
                                <span className="text-slate-500">Bio: </span>
                                {submission.speaker_bio}
                            </div>
                        )}
                    </div>

                    {submission.disk_path && (
                        <a
                            href={route('portal.reviews.download', submission.id)}
                            className="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Download attachment ({submission.original_filename})
                        </a>
                    )}
                </Card>

                <Card title={myReview ? 'Update Your Review' : 'Add Your Review'}>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="score" value="Score (1-5)" />
                                <SelectInput
                                    id="score"
                                    className="mt-1 block w-full"
                                    value={form.data.score}
                                    onChange={(e) => form.setData('score', Number(e.target.value))}
                                >
                                    {[1, 2, 3, 4, 5].map((n) => (
                                        <option key={n} value={n}>
                                            {n}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel htmlFor="recommendation" value="Recommendation" />
                                <SelectInput
                                    id="recommendation"
                                    className="mt-1 block w-full"
                                    value={form.data.recommendation}
                                    onChange={(e) => form.setData('recommendation', e.target.value)}
                                >
                                    {['Accept', 'Reject', 'Maybe'].map((rec) => (
                                        <option key={rec} value={rec}>
                                            {rec}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                        </div>
                        <div>
                            <InputLabel htmlFor="comments" value="Comments" />
                            <TextareaInput
                                id="comments"
                                rows={4}
                                className="mt-1 block w-full"
                                value={form.data.comments}
                                onChange={(e) => form.setData('comments', e.target.value)}
                            />
                            <InputError className="mt-2" message={form.errors.comments} />
                        </div>
                        <div className="flex items-center gap-3">
                            <PrimaryButton
                                disabled={form.processing}
                                className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                            >
                                {myReview ? 'Update Review' : 'Save Review'}
                            </PrimaryButton>
                            <Link
                                href={route('portal.reviews.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Back to My Reviews
                            </Link>
                        </div>
                    </form>
                </Card>
            </div>
        </PortalLayout>
    );
}
