import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import CommentsPanel from '@/Components/CommentsPanel';
import DataTable from '@/Components/UI/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const priorityColors = { Low: 'slate', Medium: 'indigo', High: 'amber', Urgent: 'rose' };
const statusColors = {
    Requested: 'slate',
    'In Progress': 'indigo',
    'Waiting for Stakeholder': 'amber',
    Completed: 'emerald',
    Cancelled: 'rose',
};

const emptyRequest = {
    title: '',
    description: '',
    organization_id: '',
    contact_id: '',
    assigned_to: '',
    priority: 'Medium',
    due_date: '',
    status: 'Requested',
};

export default function RequestsTab({
    event,
    requests,
    availableUsers,
    availableOrganizations,
    availableContacts,
    requestStatuses,
    taskPriorities,
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRequest, setEditingRequest] = useState(null);
    const [commentsRequestId, setCommentsRequestId] = useState(null);
    const form = useForm(emptyRequest);

    const commentsRequest = requests.find((r) => r.id === commentsRequestId) ?? null;

    function openCreateModal() {
        setEditingRequest(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(request) {
        setEditingRequest(request);
        form.setData({
            title: request.title,
            description: request.description ?? '',
            organization_id: request.organization_id ?? '',
            contact_id: request.contact_id ?? '',
            assigned_to: request.assigned_to ?? '',
            priority: request.priority,
            due_date: request.due_date ?? '',
            status: request.status,
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        if (editingRequest) {
            form.put(route('requests.update', editingRequest.id), options);
        } else {
            form.post(route('requests.store', event.id), options);
        }
    }

    function destroyRequest(request) {
        if (confirm(`Remove the request "${request.title}"?`)) {
            router.delete(route('requests.destroy', request.id));
        }
    }

    const columns = [
        { key: 'title', header: 'Title' },
        {
            key: 'requester',
            header: 'Requested By',
            render: (request) => request.contact?.full_name ?? request.organization?.name ?? 'Internal',
        },
        {
            key: 'assignee',
            header: 'Assigned To',
            render: (request) => request.assignee?.name ?? 'Unassigned',
        },
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
        {
            key: 'comments',
            header: 'Comments',
            render: (request) => (
                <button
                    type="button"
                    onClick={() => setCommentsRequestId(request.id)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {request.comments.length}
                </button>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (request) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(request)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyRequest(request)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Card
                title="Stakeholder Requests"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Log Request
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={requests}
                    emptyMessage="No requests logged yet."
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingRequest ? 'Edit Request' : 'Log Request'}
                    </h3>

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

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="organization_id" value="Organization" />
                            <SelectInput
                                id="organization_id"
                                className="mt-1 block w-full"
                                value={form.data.organization_id}
                                onChange={(e) => form.setData('organization_id', e.target.value)}
                            >
                                <option value="">None</option>
                                {availableOrganizations.map((org) => (
                                    <option key={org.id} value={org.id}>
                                        {org.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="contact_id" value="Contact" />
                            <SelectInput
                                id="contact_id"
                                className="mt-1 block w-full"
                                value={form.data.contact_id}
                                onChange={(e) => form.setData('contact_id', e.target.value)}
                            >
                                <option value="">None</option>
                                {availableContacts.map((contact) => (
                                    <option key={contact.id} value={contact.id}>
                                        {contact.full_name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel htmlFor="assigned_to" value="Assigned To" />
                            <SelectInput
                                id="assigned_to"
                                className="mt-1 block w-full"
                                value={form.data.assigned_to}
                                onChange={(e) => form.setData('assigned_to', e.target.value)}
                            >
                                <option value="">Unassigned</option>
                                {availableUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="priority" value="Priority" />
                            <SelectInput
                                id="priority"
                                className="mt-1 block w-full"
                                value={form.data.priority}
                                onChange={(e) => form.setData('priority', e.target.value)}
                            >
                                {taskPriorities.map((priority) => (
                                    <option key={priority} value={priority}>
                                        {priority}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={form.data.due_date}
                                onChange={(e) => form.setData('due_date', e.target.value)}
                            />
                        </div>
                    </div>

                    {editingRequest && (
                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <SelectInput
                                id="status"
                                className="mt-1 block w-full"
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                            >
                                {requestStatuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={form.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {editingRequest ? 'Save Changes' : 'Log Request'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            {commentsRequest && (
                <CommentsPanel
                    show={commentsRequest !== null}
                    onClose={() => setCommentsRequestId(null)}
                    title={commentsRequest.title}
                    comments={commentsRequest.comments}
                    storeUrl={route('requests.comments.store', commentsRequest.id)}
                    allowVisibilityToggle
                />
            )}
        </>
    );
}
