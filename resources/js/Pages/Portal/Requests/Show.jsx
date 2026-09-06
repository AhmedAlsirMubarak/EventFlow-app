import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import TextareaInput from '@/Components/TextareaInput';
import PortalLayout from '@/Layouts/PortalLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, useForm, usePoll } from '@inertiajs/react';

const priorityColors = { Low: 'slate', Medium: 'indigo', High: 'amber', Urgent: 'rose' };
const statusColors = {
    Requested: 'slate',
    'In Progress': 'indigo',
    'Waiting for Stakeholder': 'amber',
    Completed: 'emerald',
    Cancelled: 'rose',
};

export default function Show({ request }) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });
    const form = useForm({ body: '' });

    function submit(e) {
        e.preventDefault();
        form.post(route('portal.requests.comments.store', request.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <PortalLayout header={request.title}>
            <Head title={request.title} />

            <div className="space-y-6">
                <Card title="Request Details">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-slate-500">Event</dt>
                            <dd className="mt-1 text-sm text-slate-900">{request.event.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Priority</dt>
                            <dd className="mt-1">
                                <Badge color={priorityColors[request.priority] ?? 'slate'}>
                                    {request.priority}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Status</dt>
                            <dd className="mt-1">
                                <Badge color={statusColors[request.status] ?? 'slate'}>
                                    {request.status}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Needed By</dt>
                            <dd className="mt-1 text-sm text-slate-900">{request.due_date ?? '—'}</dd>
                        </div>
                        {request.description && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-slate-500">Description</dt>
                                <dd className="mt-1 whitespace-pre-line text-sm text-slate-900">
                                    {request.description}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Card>

                <Card title="Conversation">
                    <ul className="space-y-3">
                        {request.comments.length === 0 && (
                            <li className="text-sm text-slate-400">No messages yet.</li>
                        )}
                        {request.comments.map((comment) => (
                            <li key={comment.id} className="rounded-lg bg-slate-50 p-3 text-sm">
                                <span className="font-medium text-slate-900">
                                    {comment.user?.name ?? comment.contact?.full_name ?? 'Unknown'}
                                </span>
                                <p className="mt-1 whitespace-pre-line text-slate-700">{comment.body}</p>
                            </li>
                        ))}
                    </ul>

                    <form onSubmit={submit} className="mt-4 space-y-2 border-t border-slate-100 pt-4">
                        <TextareaInput
                            rows={2}
                            className="block w-full"
                            placeholder="Reply to the event team"
                            value={form.data.body}
                            onChange={(e) => form.setData('body', e.target.value)}
                        />
                        <PrimaryButton
                            disabled={form.processing || !form.data.body}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            Send
                        </PrimaryButton>
                    </form>
                </Card>
            </div>
        </PortalLayout>
    );
}
