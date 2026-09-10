import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import PortalLayout from '@/Layouts/PortalLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, useForm, usePoll } from '@inertiajs/react';
import { useState } from 'react';

const priorityColors = { Low: 'slate', Medium: 'indigo', High: 'amber', Urgent: 'rose' };
const statusColors = {
    Requested: 'slate',
    'In Progress': 'indigo',
    'Waiting for Stakeholder': 'amber',
    Completed: 'emerald',
    Cancelled: 'rose',
};

export default function Index({ requests, events }) {
    usePoll(POLL_INTERVAL);
    const [modalOpen, setModalOpen] = useState(false);
    const form = useForm({
        event_id: '',
        title: '',
        description: '',
        priority: 'Medium',
        due_date: '',
    });

    function openCreateModal() {
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        form.post(route('portal.requests.store'), {
            onSuccess: () => setModalOpen(false),
        });
    }

    const columns = [
        {
            key: 'title',
            header: 'Request',
            render: (request) => (
                <Link
                    href={route('portal.requests.show', request.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {request.title}
                </Link>
            ),
        },
        { key: 'event', header: 'Event', render: (request) => request.event.name },
        {
            key: 'priority',
            header: 'Priority',
            render: (request) => (
                <Badge color={priorityColors[request.priority] ?? 'slate'}>{request.priority}</Badge>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (request) => (
                <Badge color={statusColors[request.status] ?? 'slate'}>{request.status}</Badge>
            ),
        },
        { key: 'due_date', header: 'Due', render: (request) => request.due_date ?? '—' },
    ];

    return (
        <PortalLayout header="My Requests">
            <Head title="My Requests" />

            <Card
                title="Requests submitted to the event team"
                actions={
                    events.length > 0 && (
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            New Request
                        </button>
                    )
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={requests}
                    emptyMessage="You haven't submitted any requests yet."
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">New Request</h3>

                    <div>
                        <InputLabel htmlFor="event_id" value="Event" />
                        <SelectInput
                            id="event_id"
                            className="mt-1 block w-full"
                            value={form.data.event_id}
                            onChange={(e) => form.setData('event_id', e.target.value)}
                            required
                        >
                            <option value="">Select an event</option>
                            {events.map((event) => (
                                <option key={event.id} value={event.id}>
                                    {event.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError className="mt-2" message={form.errors.event_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={form.errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextareaInput
                            id="description"
                            rows={3}
                            className="mt-1 block w-full"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="priority" value="Priority" />
                            <SelectInput
                                id="priority"
                                className="mt-1 block w-full"
                                value={form.data.priority}
                                onChange={(e) => form.setData('priority', e.target.value)}
                            >
                                {['Low', 'Medium', 'High', 'Urgent'].map((priority) => (
                                    <option key={priority} value={priority}>
                                        {priority}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="due_date" value="Needed By" />
                            <TextInput
                                id="due_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={form.data.due_date}
                                onChange={(e) => form.setData('due_date', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={form.processing}>Submit Request</PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>
        </PortalLayout>
    );
}
